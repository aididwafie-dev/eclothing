<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Processing orders left uncollected for too long. Run daily by
 * `orders:process-collection-timeouts` (see routes/console.php).
 *
 * A member has 14 days from the admin moving their order to Processing to
 * collect it. Past that, the order reverts to Pending so it falls back into
 * the store's working queue instead of sitting invisible in Processing --
 * but unlike a manual revert (AdminController::updateUniformOrderStatus),
 * the KEW.PS-8 approver snapshot (approved_by_admin_id/name/position,
 * approved_at) is kept. The approval itself was never undone, only the
 * handover; reopening the queue slot should not blank the form that
 * recorded who certified it.
 */
class OrderCollectionTimeoutService
{
    public const COLLECTION_WINDOW_DAYS = 14;

    public function __construct(
        private OrderNotificationService $notifications,
        private AdminNotificationService $adminNotifications,
    ) {
    }

    public function tableReady(): bool
    {
        try {
            return Schema::hasTable('orders') && Schema::hasColumn('orders', 'processing_at');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return int orders reverted */
    public function process(): int
    {
        if (!$this->tableReady()) {
            return 0;
        }

        $cutoff = now()->subDays(self::COLLECTION_WINDOW_DAYS);

        $orders = DB::table('orders')
            ->where('status', '=', '5')
            ->where('deleted', '=', 0)
            ->whereNotNull('processing_at')
            ->where('processing_at', '<=', $cutoff)
            ->get();

        $reverted = 0;
        foreach ($orders as $order) {
            if ($this->revert($order)) {
                $reverted++;
            }
        }

        return $reverted;
    }

    private function revert($order): bool
    {
        try {
            // approved_by_admin_id/name/position and approved_at are
            // deliberately absent from this update -- see the class docblock.
            DB::table('orders')->where('id', '=', $order->id)->update([
                'status' => '1',
                'collection_date' => null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        $this->notifications->notify(
            (int) $order->user_id,
            (int) $order->id,
            OrderNotificationService::TYPE_COLLECTION_TIMEOUT,
            'Pesanan Dikembalikan ke Menunggu',
            'Pesanan uniform #' . (int) $order->id . ' anda belum diambil dalam tempoh '
                . self::COLLECTION_WINDOW_DAYS . ' hari selepas diproses, jadi ia telah '
                . 'dikembalikan ke status Menunggu. Sila hubungi stor untuk mengambil '
                . 'pakaian seragam anda.'
        );

        $this->adminNotifications->notifySuperadmins(
            null,
            (int) $order->id,
            AdminNotificationService::TYPE_ORDER_REVERTED,
            'Pesanan Tamat Tempoh Pengambilan',
            'Pesanan #' . (int) $order->id . ' tidak diambil dalam tempoh ' . self::COLLECTION_WINDOW_DAYS
                . ' hari dan telah dikembalikan ke status Menunggu secara automatik.'
        );

        return true;
    }
}
