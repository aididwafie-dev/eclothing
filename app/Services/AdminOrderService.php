<?php

namespace App\Services;

use App\Exceptions\AdminOrderActionRefusedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

/**
 * The Uniform Orders admin workflow -- list, detail, status update --
 * extracted out of AdminController so the web Blade pages and the
 * Api\Admin\OrderController JSON endpoints run the exact same rules
 * instead of two copies drifting apart. Mirrors ClothingSizeService's
 * role in the member-facing side.
 */
class AdminOrderService
{
    use \App\Http\Controllers\Concerns\BuildsKewPs8Report;

    public function __construct(
        private OrderStatusService $orderStatus,
        private AdminRoleService $adminRoles,
    ) {
    }

    public function listQuery()
    {
        $itemCounts = DB::table('ordered_clothes')
            ->select('order_id', DB::raw('COUNT(*) as items_count'))
            ->groupBy('order_id');

        $selectColumns = [
            'orders.id',
            'orders.user_id',
            'orders.uniforms_id',
            'orders.created_at',
            'orders.updated_at',
            'gen_users.s_id',
            'personal_details.name',
            'units.value as unit_name',
            'uniforms.uniform_type',
            'uniforms.uniform_name',
            DB::raw('COALESCE(order_items.items_count, 0) as items_count'),
        ];

        if ($this->orderStatus->hasOrderLifecycleColumns()) {
            $selectColumns[] = 'orders.status';
            $selectColumns[] = 'orders.remarks';
            $selectColumns[] = 'orders.collection_date';
        } else {
            $selectColumns[] = DB::raw("'1' as status");
            $selectColumns[] = DB::raw('NULL as remarks');
            $selectColumns[] = DB::raw('NULL as collection_date');
        }

        return DB::table('orders')
            ->leftJoin('gen_users', 'orders.user_id', '=', 'gen_users.id')
            ->leftJoin('personal_details', 'orders.user_id', '=', 'personal_details.user_id')
            ->leftJoin('units', 'personal_details.unit', '=', 'units.id')
            ->leftJoin('uniforms', 'orders.uniforms_id', '=', 'uniforms.id')
            ->leftJoinSub($itemCounts, 'order_items', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id');
            })
            ->select($selectColumns)
            ->where('orders.deleted', '=', 0);
    }

    /**
     * @return array{0: array<string, string>, 1: array<int, string>, 2: string}
     */
    public function statusFilter(string $role, ?string $requested): array
    {
        $statusOptions = $this->orderStatus->filterableStatuses();

        $visibleStatusKeys = $this->adminRoles->visibleOrderStatusKeys($role, array_keys($statusOptions));
        $statusOptions = array_intersect_key($statusOptions, array_flip($visibleStatusKeys));
        $defaultStatus = $this->adminRoles->defaultOrderStatusKey($role);

        $status = strtolower(trim((string) ($requested ?? $defaultStatus)));
        if ($status !== 'all' && !isset($statusOptions[$status])) {
            $status = $defaultStatus;
        }

        return [$statusOptions, $visibleStatusKeys, $status];
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: int}
     */
    public function unitFilter(?int $baseId, $requested): array
    {
        $query = DB::table('units')->orderBy('value');
        if ($baseId !== null && $baseId > 0) {
            $query->where('base_hq', '=', $baseId);
        }
        $unitOptions = $query->get();

        $unit = (int) $requested;
        if ($unit > 0 && !$unitOptions->contains(fn ($u) => (int) $u->id === $unit)) {
            $unit = 0;
        }

        return [$unitOptions, $unit];
    }

    public function adminBaseId(int $adminId): int
    {
        try {
            return (int) DB::table('admins')->where('id', '=', $adminId)->value('base_id');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Plain order row, no joins -- enough to validate and update. */
    public function findOrder(int $orderId): ?object
    {
        return DB::table('orders')->where('deleted', '=', 0)->where('id', '=', $orderId)->first();
    }

    /** Order row joined for display, normalized through OrderStatusService. */
    public function findOrderDetail(int $orderId): ?object
    {
        $order = DB::table('orders')
            ->leftJoin('gen_users', 'orders.user_id', '=', 'gen_users.id')
            ->leftJoin('personal_details', 'orders.user_id', '=', 'personal_details.user_id')
            ->leftJoin('units', 'personal_details.unit', '=', 'units.id')
            ->leftJoin('uniforms', 'orders.uniforms_id', '=', 'uniforms.id')
            ->select(
                'orders.*',
                'gen_users.s_id',
                'gen_users.email',
                'personal_details.name',
                'units.value as unit_name',
                'uniforms.uniform_type',
                'uniforms.uniform_name',
                'uniforms.uniform_photo'
            )
            ->where('orders.deleted', '=', 0)
            ->where('orders.id', '=', $orderId)
            ->first();

        return $order ? $this->orderStatus->normalizeOrderLifecycle($order) : null;
    }

    /**
     * Whether the order was placed by the admin's own member account,
     * matched on Service ID or email since the two account types are
     * separate.
     */
    public function isOwnOrder(int $adminId, object $order): bool
    {
        $admin = DB::table('admins')->where('id', '=', $adminId)->first();
        $member = DB::table('gen_users')->where('id', '=', (int) ($order->user_id ?? 0))->first();
        if (!$admin || !$member) {
            return false;
        }

        $adminServiceId = trim((string) ($admin->s_id ?? '')) ?: trim((string) ($admin->username ?? ''));
        $memberServiceId = trim((string) ($member->s_id ?? ''));
        if ($adminServiceId !== '' && strcasecmp($adminServiceId, $memberServiceId) === 0) {
            return true;
        }

        $adminEmail = strtolower(trim((string) ($admin->email ?? '')));
        return $adminEmail !== '' && $adminEmail === strtolower(trim((string) ($member->email ?? '')));
    }

    public function canRecordIssueVouchers(string $role): bool
    {
        return $this->adminRoles->normalize($role) === AdminRoleService::ORDERS
            && Schema::hasColumn('ordered_clothes', 'issue_voucher');
    }

    public function canApproveQuantities(string $role): bool
    {
        return $this->adminRoles->normalize($role) === AdminRoleService::SUPERADMIN
            && Schema::hasColumn('ordered_clothes', 'approved_quantity');
    }

    /**
     * Collection date for an order approved at $approvedAt. The store is
     * open Monday to Friday: approved on a weekday up to 17:00, it can be
     * collected at once; approved after 17:00 or at the weekend, it is
     * collected at 09:00 on the next weekday.
     */
    public function collectionDateForApproval(int $approvedAt): string
    {
        $isWeekday = (int) date('N', $approvedAt) <= 5;
        if ($isWeekday && date('H:i:s', $approvedAt) <= '17:00:00') {
            return date('Y-m-d H:i:s', $approvedAt);
        }

        $next = strtotime('+1 day', $approvedAt);
        while ((int) date('N', $next) > 5) {
            $next = strtotime('+1 day', $next);
        }

        return date('Y-m-d', $next) . ' 09:00:00';
    }

    /** @return array<string, string> */
    public function validationRules(array $allowedStatuses, ?string $status): array
    {
        $rules = [
            'order_id' => 'required|integer',
            'status' => 'required|in:' . implode(',', $allowedStatuses),
            'remarks' => 'nullable|string|max:1000',
            'collection_date' => 'nullable|date',
            'collection_time' => 'nullable|date_format:H:i',
            'approved_quantity' => 'nullable|array',
            'approved_quantity.*' => 'nullable|integer|min:0|max:999',
            'issue_voucher' => 'nullable|array',
            'issue_voucher.*' => 'nullable|string|max:100',
        ];

        // A rejection reaches the member with the remarks as the reason, so
        // it cannot be blank.
        if ((string) $status === '2') {
            $rules['remarks'] = 'required|string|max:1000';
        }

        return $rules;
    }

    public function validationMessages(): array
    {
        return [
            'remarks.required' => 'Please key in the remarks. A rejected order must tell the member why.',
        ];
    }

    public function validate(array $input, array $allowedStatuses): \Illuminate\Validation\Validator
    {
        return Validator::make(
            $input,
            $this->validationRules($allowedStatuses, $input['status'] ?? null),
            $this->validationMessages()
        );
    }

    /**
     * Applies a validated status change to $order. Throws
     * AdminOrderActionRefusedException for the two business rules that are
     * not basic input validation: an order outside the role's queue, or
     * approving one's own order. Returns the order row as it stands after
     * the update.
     *
     * @param array{status: string, remarks?: ?string, collection_date?: ?string, collection_time?: ?string, approved_quantity?: array, issue_voucher?: array} $validated
     */
    public function updateStatus(object $order, string $role, int $actingAdminId, array $validated): object
    {
        if (!$this->orderStatus->hasOrderLifecycleColumns()) {
            throw new AdminOrderActionRefusedException('Please run the latest migration before updating order status.');
        }

        $currentStatusKey = $this->orderStatus->orderStatusMeta($order->status ?? null)['key'];
        if (!$this->adminRoles->canSeeOrderStatus($role, $currentStatusKey)) {
            throw new AdminOrderActionRefusedException('This order is not in your queue yet.');
        }

        $status = trim((string) $validated['status']);

        if ($status === '3' && $this->isOwnOrder($actingAdminId, $order)) {
            throw new AdminOrderActionRefusedException('You cannot approve your own order. Another admin must approve it.');
        }

        $remarks = trim((string) ($validated['remarks'] ?? ''));
        $collectionDate = $validated['collection_date'] ?? null;
        // A date with no time is booked for 09:00, the start of the day's collection.
        $collectionTime = trim((string) ($validated['collection_time'] ?? '')) ?: '09:00';

        $updateData = [
            'status' => $status,
            'remarks' => $remarks !== '' ? $remarks : null,
            'collection_date' => $collectionDate ? date('Y-m-d', strtotime($collectionDate)) . ' ' . $collectionTime . ':00' : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($status === '2') {
            $updateData['collection_date'] = null;
        }

        // Approving with no collection date set books one; a date the admin
        // keys in is kept as it is.
        if ($status === '3' && empty($collectionDate)) {
            $updateData['collection_date'] = $this->collectionDateForApproval(time());
        }

        $prevStatus = trim((string) ($order->status ?? ''));

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'completed_at')) {
            if ($status === '6') {
                if ($prevStatus !== '6') {
                    $updateData['completed_at'] = date('Y-m-d H:i:s');
                }
            } else {
                $updateData['completed_at'] = null;
            }
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'processing_at')) {
            if ($status === '5' && $prevStatus !== '5') {
                $updateData['processing_at'] = date('Y-m-d H:i:s');
            }
        }

        $hasApproverColumns = Schema::hasTable('orders')
            && Schema::hasColumn('orders', 'approved_by_admin_id')
            && Schema::hasColumn('orders', 'approved_at');
        $hasApproverSnapshot = Schema::hasTable('orders')
            && Schema::hasColumn('orders', 'approved_by_name')
            && Schema::hasColumn('orders', 'approved_by_position');

        if ($hasApproverColumns) {
            $transitioningToApproved = $status === '3' && $prevStatus !== '3';
            if ($transitioningToApproved) {
                if ($actingAdminId > 0) {
                    $updateData['approved_by_admin_id'] = $actingAdminId;
                    $updateData['approved_at'] = date('Y-m-d H:i:s');

                    if ($hasApproverSnapshot) {
                        $approver = DB::table('admins')->where('id', '=', $actingAdminId)->first();
                        $updateData['approved_by_name'] = $approver ? $this->kewPs8SignatoryNameForAdmin($approver) : null;
                        $updateData['approved_by_position'] = $approver ? (trim((string) ($approver->jawatan ?? '')) ?: null) : null;
                    }
                }
            } elseif (!in_array($status, ['3', '5', '6'], true)) {
                $updateData['approved_by_admin_id'] = null;
                $updateData['approved_at'] = null;
                if ($hasApproverSnapshot) {
                    $updateData['approved_by_name'] = null;
                    $updateData['approved_by_position'] = null;
                }
            }
        }

        DB::table('orders')->where('id', '=', $order->id)->update($updateData);

        $this->notifyAdminsOfStatusChange($actingAdminId, (int) $order->id, $prevStatus, $status);

        if ($this->canRecordIssueVouchers($role) && is_array($validated['issue_voucher'] ?? null)) {
            $vouchers = $validated['issue_voucher'];
            foreach (DB::table('ordered_clothes')->where('order_id', '=', $order->id)->pluck('id') as $lineId) {
                if (!array_key_exists($lineId, $vouchers)) {
                    continue;
                }
                $voucher = mb_substr(trim((string) $vouchers[$lineId]), 0, 100);
                DB::table('ordered_clothes')->where('id', '=', $lineId)->update(['issue_voucher' => $voucher !== '' ? $voucher : null]);
            }
        }

        if ($status === '3' && $this->canApproveQuantities($role)) {
            $approvedQuantities = (array) ($validated['approved_quantity'] ?? []);
            foreach (DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get() as $line) {
                $requested = max(1, (int) ($line->quantity ?? 1));
                $value = $approvedQuantities[$line->id] ?? null;
                $approved = ($value === null || $value === '') ? $requested : min((int) $value, $requested);
                DB::table('ordered_clothes')->where('id', '=', $line->id)->update(['approved_quantity' => $approved]);
            }
        }

        $updatedOrder = DB::table('orders')->where('id', '=', $order->id)->first();

        try {
            if ($updatedOrder) {
                app(OrderNotificationService::class)->orderUpdated($order, $updatedOrder);
            }
        } catch (\Throwable $e) {
            \Log::warning('Order update notification failed: ' . $e->getMessage());
        }

        return $updatedOrder ?? $order;
    }

    /**
     * Tells the admins whose queue actually just changed, not every admin.
     * Mirrors the status coverage of the member-facing
     * OrderNotificationService::diff(), just for the other audience.
     */
    private function notifyAdminsOfStatusChange(int $actingAdminId, int $orderId, string $prevStatus, string $newStatus): void
    {
        if ($prevStatus === $newStatus) {
            return;
        }

        $service = app(AdminNotificationService::class);

        try {
            if ($newStatus === '3') {
                $service->notifyOrdersAdmins(
                    $actingAdminId ?: null,
                    $orderId,
                    AdminNotificationService::TYPE_ORDER_APPROVED,
                    'Pesanan Diluluskan',
                    'Pesanan #' . $orderId . ' telah diluluskan dan kini dalam baris gilir pemprosesan.'
                );
            } elseif ($newStatus === '1') {
                $service->notifySuperadmins(
                    $actingAdminId ?: null,
                    $orderId,
                    AdminNotificationService::TYPE_ORDER_REVERTED,
                    'Pesanan Dikembalikan ke Menunggu',
                    'Pesanan #' . $orderId . ' telah dikembalikan ke status Menunggu.'
                );
            } elseif ($newStatus === '2') {
                $service->notifySuperadmins(
                    $actingAdminId ?: null,
                    $orderId,
                    AdminNotificationService::TYPE_ORDER_REJECTED,
                    'Pesanan Ditolak',
                    'Pesanan #' . $orderId . ' telah ditolak.'
                );
            } elseif ($newStatus === '5') {
                $service->notifySuperadmins(
                    $actingAdminId ?: null,
                    $orderId,
                    AdminNotificationService::TYPE_ORDER_PROCESSING,
                    'Pesanan Sedang Diproses',
                    'Pesanan #' . $orderId . ' kini sedang diproses di stor.'
                );
            } elseif ($newStatus === '6') {
                $service->notifySuperadmins(
                    $actingAdminId ?: null,
                    $orderId,
                    AdminNotificationService::TYPE_ORDER_COMPLETED,
                    'Pesanan Selesai',
                    'Pesanan #' . $orderId . ' telah selesai.'
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Admin notification failed: ' . $e->getMessage());
        }
    }
}
