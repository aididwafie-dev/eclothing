<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * The admin-side counterpart to OrderNotificationService: order activity
 * admins need to know about, fanned out to whichever admins it is actually
 * that role's job to act on -- a superadmin for anything touching Pending
 * (new orders, edits, the system reverting one back), an "orders" admin for
 * anything entering their Processing queue. Mirrors
 * AdminRoleService::ORDERS_VISIBLE_STATUSES rather than guessing who cares.
 */
class AdminNotificationService
{
    public const TYPE_NEW_ORDER = 'admin_new_order';
    public const TYPE_ORDER_EDITED = 'admin_order_edited';
    public const TYPE_ORDER_APPROVED = 'admin_order_approved';
    public const TYPE_ORDER_REVERTED = 'admin_order_reverted';
    public const TYPE_ORDER_REJECTED = 'admin_order_rejected';
    public const TYPE_ORDER_PROCESSING = 'admin_order_processing';
    public const TYPE_ORDER_COMPLETED = 'admin_order_completed';

    public function storageReady(): bool
    {
        try {
            return Schema::hasTable('admin_notifications');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Every active superadmin, minus the admin who caused this (if any). */
    public function notifySuperadmins(?int $excludeAdminId, ?int $orderId, string $type, string $title, string $body): int
    {
        return $this->notifyRole('superadmin', $excludeAdminId, $orderId, $type, $title, $body);
    }

    /** Every active "orders" admin, minus the admin who caused this (if any). */
    public function notifyOrdersAdmins(?int $excludeAdminId, ?int $orderId, string $type, string $title, string $body): int
    {
        return $this->notifyRole('orders', $excludeAdminId, $orderId, $type, $title, $body);
    }

    private function notifyRole(string $role, ?int $excludeAdminId, ?int $orderId, string $type, string $title, string $body): int
    {
        if (!$this->storageReady()) {
            return 0;
        }

        try {
            $hasRoleColumn = Schema::hasColumn('admins', 'role');

            $query = DB::table('admins')->where('status', '=', 1);
            // No role column means every admin reads as superadmin (see
            // AdminRoleService::normalize) -- so without it, "orders" has no
            // recipients and "superadmin" means every active admin.
            if ($hasRoleColumn) {
                $query->where(function ($q) use ($role) {
                    $role === 'orders'
                        ? $q->where('role', '=', 'orders')
                        : $q->where(function ($q2) {
                            $q2->whereNull('role')->orWhere('role', '!=', 'orders');
                        });
                });
            } elseif ($role === 'orders') {
                return 0;
            }

            if ($excludeAdminId) {
                $query->where('id', '!=', $excludeAdminId);
            }

            $adminIds = $query->pluck('id')->all();
        } catch (\Throwable $e) {
            Log::warning('AdminNotificationService: could not resolve recipients: ' . $e->getMessage());
            return 0;
        }

        if (empty($adminIds)) {
            return 0;
        }

        $payload = $orderId ? json_encode(['type' => $type, 'order_id' => (string) $orderId]) : json_encode(['type' => $type]);
        $now = now();
        $rows = array_map(fn ($adminId) => [
            'admin_id' => $adminId,
            'order_id' => $orderId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'payload' => $payload,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $adminIds);

        try {
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('admin_notifications')->insert($chunk);
            }
        } catch (\Throwable $e) {
            Log::warning('AdminNotificationService: write failed: ' . $e->getMessage());
            return 0;
        }

        return count($adminIds);
    }
}
