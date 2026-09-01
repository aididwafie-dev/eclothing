<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

/**
 * Order status normalization -- previously copy-pasted verbatim as
 * private methods on both AdminController and DashboardController.
 * Extracted so the mobile API's OrderController can share the exact
 * same status mapping without a third copy.
 */
class OrderStatusService
{
    private ?bool $hasLifecycleColumns = null;

    public function hasOrderLifecycleColumns(): bool
    {
        if ($this->hasLifecycleColumns !== null) {
            return $this->hasLifecycleColumns;
        }

        try {
            $this->hasLifecycleColumns = Schema::hasTable('orders')
                && Schema::hasColumn('orders', 'status')
                && Schema::hasColumn('orders', 'remarks')
                && Schema::hasColumn('orders', 'collection_date');
        } catch (\Throwable $e) {
            $this->hasLifecycleColumns = false;
        }

        return $this->hasLifecycleColumns;
    }

    public function normalizeOrderLifecycle($order)
    {
        if (!$order) {
            return $order;
        }

        $statusMeta = $this->orderStatusMeta($order->status ?? null);

        $order->status = $statusMeta['code'];
        $order->status_key = $statusMeta['key'];
        $order->status_label = $statusMeta['label'];
        $order->status_class = $statusMeta['class'];
        $order->remarks = $order->remarks ?? null;
        $order->collection_date = $order->collection_date ?? null;

        return $order;
    }

    /**
     * A member may only change an order while it is still Pending. Once the
     * store moves it to Processing -- or it is Approved/Rejected/Expired --
     * a re-checkout must not silently reset it to Pending and wipe the
     * admin's remarks and collection date.
     *
     * On schemas predating the lifecycle columns there is no status to read,
     * so every order behaves as Pending and stays editable, exactly as it
     * did before this rule existed.
     */
    public function isOrderEditable($status): bool
    {
        if (!$this->hasOrderLifecycleColumns()) {
            return true;
        }

        return $this->orderStatusMeta($status)['key'] === 'pending';
    }

    public function orderStatusMeta($status): array
    {
        $status = strtolower(trim((string) $status));

        $statusMap = [
            '1' => ['code' => '1', 'key' => 'pending', 'label' => 'Pending', 'class' => 'status-pending'],
            '2' => ['code' => '2', 'key' => 'rejected', 'label' => 'Rejected', 'class' => 'status-rejected'],
            '3' => ['code' => '3', 'key' => 'approved', 'label' => 'Approved', 'class' => 'status-approved'],
            '4' => ['code' => '4', 'key' => 'expired', 'label' => 'Expired', 'class' => 'status-expired'],
            '5' => ['code' => '5', 'key' => 'processing', 'label' => 'Processing', 'class' => 'status-processing'],
            'pending' => ['code' => '1', 'key' => 'pending', 'label' => 'Pending', 'class' => 'status-pending'],
            'rejected' => ['code' => '2', 'key' => 'rejected', 'label' => 'Rejected', 'class' => 'status-rejected'],
            'approved' => ['code' => '3', 'key' => 'approved', 'label' => 'Approved', 'class' => 'status-approved'],
            'expired' => ['code' => '4', 'key' => 'expired', 'label' => 'Expired', 'class' => 'status-expired'],
            'processing' => ['code' => '5', 'key' => 'processing', 'label' => 'Processing', 'class' => 'status-processing'],
        ];

        return $statusMap[$status] ?? $statusMap['1'];
    }
}
