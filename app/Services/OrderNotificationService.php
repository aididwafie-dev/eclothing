<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Raises the notifications a member sees when an admin acts on their order.
 *
 * The admin screen saves status, remarks and collection date in one submit,
 * so a single save can legitimately be more than one piece of news. This
 * compares the order before and after and emits only what actually changed
 * -- re-saving a form without edits notifies nobody.
 *
 * A status change subsumes the collection date: an approval that also sets
 * the collection date reads as one "approved, collect on <date>" message
 * rather than two notifications about the same action.
 */
class OrderNotificationService
{
    public const TYPE_APPROVED = 'order_approved';
    public const TYPE_REJECTED = 'order_rejected';
    public const TYPE_REMARKS = 'order_remarks_updated';
    public const TYPE_COLLECTION_DATE = 'order_collection_date_updated';
    public const TYPE_PROCESSING = 'order_processing';

    public function __construct(
        private FcmSender $fcm,
        private OrderStatusService $orderStatus,
    ) {
    }

    public function storageReady(): bool
    {
        try {
            return Schema::hasTable('user_notifications');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function deviceStorageReady(): bool
    {
        try {
            return Schema::hasTable('device_tokens');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Compares an order's before/after state and delivers what changed.
     *
     * @param  object $before order row as it was before the update
     * @param  object $after  order row as it is now
     * @return int number of notifications raised
     */
    public function orderUpdated($before, $after): int
    {
        $messages = $this->diff($before, $after);

        if (empty($messages)) {
            return 0;
        }

        $userId = (int) ($after->user_id ?? 0);
        if ($userId <= 0) {
            return 0;
        }

        $sent = 0;
        foreach ($messages as $message) {
            if ($this->notify($userId, (int) $after->id, $message['type'], $message['title'], $message['body'])) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * The human-readable changes between two order states.
     *
     * @return array<array{type: string, title: string, body: string}>
     */
    private function diff($before, $after): array
    {
        $messages = [];

        $oldStatus = $this->orderStatus->orderStatusMeta($before->status ?? null)['key'];
        $newStatus = $this->orderStatus->orderStatusMeta($after->status ?? null)['key'];

        $oldDate = $this->normalizeDate($before->collection_date ?? null);
        $newDate = $this->normalizeDate($after->collection_date ?? null);

        $oldRemarks = trim((string) ($before->remarks ?? ''));
        $newRemarks = trim((string) ($after->remarks ?? ''));

        $orderRef = '#' . (int) ($after->id ?? 0);
        $statusChanged = $oldStatus !== $newStatus;

        if ($statusChanged && $newStatus === 'approved') {
            $body = 'Pesanan uniform ' . $orderRef . ' anda telah diluluskan.';
            if ($newDate !== '') {
                $body .= ' Tarikh kutipan: ' . $this->displayDate($newDate) . '.';
            }
            $messages[] = ['type' => self::TYPE_APPROVED, 'title' => 'Pesanan Diluluskan', 'body' => $body];
        } elseif ($statusChanged && $newStatus === 'rejected') {
            $body = 'Pesanan uniform ' . $orderRef . ' anda telah ditolak.';
            if ($newRemarks !== '') {
                $body .= ' Catatan: ' . $newRemarks;
            }
            $messages[] = ['type' => self::TYPE_REJECTED, 'title' => 'Pesanan Ditolak', 'body' => $body];
        } elseif ($statusChanged && $newStatus === 'processing') {
            $body = 'Pesanan uniform ' . $orderRef . ' anda sedang diproses.';
            if ($newDate !== '') {
                $body .= ' Tarikh kutipan: ' . $this->displayDate($newDate) . '.';
            }
            $messages[] = ['type' => self::TYPE_PROCESSING, 'title' => 'Pesanan Diproses', 'body' => $body];
        }

        // Only report the date on its own when no status change already
        // carried it, otherwise the member gets the same news twice.
        $dateReportedByStatus = $statusChanged && in_array($newStatus, ['approved', 'rejected', 'processing'], true);

        if (!$dateReportedByStatus && $oldDate !== $newDate) {
            $body = $newDate === ''
                ? 'Tarikh kutipan bagi pesanan ' . $orderRef . ' telah dibatalkan.'
                : 'Tarikh kutipan bagi pesanan ' . $orderRef . ' ditetapkan pada ' . $this->displayDate($newDate) . '.';
            $messages[] = ['type' => self::TYPE_COLLECTION_DATE, 'title' => 'Tarikh Kutipan Dikemaskini', 'body' => $body];
        }

        $remarksReportedByStatus = $statusChanged && $newStatus === 'rejected' && $newRemarks !== '';

        if (!$remarksReportedByStatus && $oldRemarks !== $newRemarks) {
            $body = $newRemarks === ''
                ? 'Catatan bagi pesanan ' . $orderRef . ' telah dikosongkan.'
                : 'Catatan bagi pesanan ' . $orderRef . ': ' . $newRemarks;
            $messages[] = ['type' => self::TYPE_REMARKS, 'title' => 'Catatan Dikemaskini', 'body' => $body];
        }

        return $messages;
    }

    /**
     * Records one notification and attempts push delivery.
     *
     * Recording comes first and is what the return value reflects: push is
     * best-effort, and a failure there must not lose the message or break
     * the admin's save.
     */
    public function notify(int $userId, ?int $orderId, string $type, string $title, string $body): bool
    {
        if (!$this->storageReady()) {
            return false;
        }

        $payload = ['type' => $type];
        if ($orderId) {
            $payload['order_id'] = (string) $orderId;
        }

        try {
            DB::table('user_notifications')->insert([
                'gen_user_id' => $userId,
                'order_id' => $orderId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'payload' => json_encode($payload),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Notification could not be recorded: ' . $e->getMessage());
            return false;
        }

        $this->push($userId, $title, $body, $payload);

        return true;
    }

    /** Best-effort push to every device registered to the user. */
    private function push(int $userId, string $title, string $body, array $payload): void
    {
        if (!$this->fcm->isConfigured() || !$this->deviceStorageReady()) {
            return;
        }

        try {
            $tokens = DB::table('device_tokens')
                ->where('gen_user_id', '=', $userId)
                ->pluck('token')
                ->all();
        } catch (\Throwable $e) {
            return;
        }

        if (empty($tokens)) {
            return;
        }

        $deadTokens = $this->fcm->send($tokens, $title, $body, $payload);

        // Drop tokens FCM says are gone so the table does not grow stale.
        if (!empty($deadTokens)) {
            try {
                DB::table('device_tokens')->whereIn('token', $deadTokens)->delete();
            } catch (\Throwable $e) {
                // Pruning is housekeeping; losing it changes nothing for the user.
            }
        }
    }

    /** 'Y-m-d', or '' for no date. Guards against differing stored formats. */
    private function normalizeDate($value): string
    {
        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function displayDate(string $isoDate): string
    {
        $timestamp = strtotime($isoDate);

        return $timestamp ? date('d/m/Y', $timestamp) : $isoDate;
    }
}
