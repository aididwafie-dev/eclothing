<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\AdminOrderActionRefusedException;
use App\Http\Controllers\Controller;
use App\Services\AdminOrderService;
use App\Services\AdminRoleService;
use App\Services\OrderStatusService;
use App\Services\OrderUniformService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to AdminController's uniformOrdersList/uniformOrdersData/
 * uniformOrderDetail/updateUniformOrderStatus, for the admin side of the
 * Next.js UI. Shares every rule with the Blade pages through
 * AdminOrderService -- nothing here re-derives role, status or ownership
 * logic on its own.
 */
class OrderController extends Controller
{
    use \App\Http\Controllers\Concerns\BuildsKewPs8Report;

    public function __construct(
        private AdminOrderService $adminOrders,
        private AdminRoleService $adminRoles,
        private OrderStatusService $orderStatus,
    ) {
    }

    private function role(Request $request): string
    {
        $admin = $request->attributes->get('admin');
        return $this->adminRoles->roleFor($admin->id);
    }

    public function index(Request $request)
    {
        $admin = $request->attributes->get('admin');
        $role = $this->role($request);

        [$statusOptions, $visibleStatusKeys, $status] = $this->adminOrders->statusFilter($role, $request->query('status'));
        [$unitOptions, $unit] = $this->adminOrders->unitFilter($this->adminOrders->adminBaseId((int) $admin->id), $request->query('unit'));

        $query = $this->adminOrders->listQuery();
        $this->orderStatus->applyStatusKeysFilter($query, $visibleStatusKeys);
        if ($unit > 0) {
            $query->where('personal_details.unit', '=', $unit);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $orderId = ltrim($search, '#');
            $query->where(function ($q) use ($like, $orderId) {
                if (ctype_digit($orderId)) {
                    $q->orWhere('orders.id', '=', (int) $orderId);
                }
                $q->orWhere('gen_users.s_id', 'like', $like)
                    ->orWhere('personal_details.name', 'like', $like)
                    ->orWhere('units.value', 'like', $like)
                    ->orWhere('uniforms.uniform_type', 'like', $like)
                    ->orWhere('uniforms.uniform_name', 'like', $like)
                    ->orWhereExists(function ($lines) use ($like) {
                        $lines->select(DB::raw(1))
                            ->from('ordered_clothes')
                            ->join('uniforms as line_uniforms', 'line_uniforms.id', '=', DB::raw('COALESCE(ordered_clothes.uniforms_id, orders.uniforms_id)'))
                            ->whereColumn('ordered_clothes.order_id', 'orders.id')
                            ->where(function ($u) use ($like) {
                                $u->where('line_uniforms.uniform_type', 'like', $like)
                                    ->orWhere('line_uniforms.uniform_name', 'like', $like);
                            });
                    });
            });
        } elseif ($status !== 'all') {
            $this->orderStatus->applyStatusFilter($query, $status);
        }

        $recordsFiltered = (clone $query)->count();

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('perPage', 25)));

        $pageOrders = $query
            ->orderBy(DB::raw('COALESCE(orders.updated_at, orders.created_at)'), 'desc')
            ->orderBy('orders.id', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $uniformLabels = app(OrderUniformService::class)->labelsByOrder($pageOrders->pluck('id')->all());

        $orders = $pageOrders->map(function ($order) use ($uniformLabels) {
            $order = $this->orderStatus->normalizeOrderLifecycle($order);
            return [
                'id' => (string) $order->id,
                'sId' => $order->s_id ?: null,
                'name' => $order->name ?: null,
                'unitName' => $order->unit_name ?: null,
                'uniformLabel' => $uniformLabels[(int) $order->id] ?? ($order->uniform_type . ($order->uniform_name ? ' (' . $order->uniform_name . ')' : '')),
                'itemCount' => (int) $order->items_count,
                'status' => $order->status_key ?? 'pending',
                'statusLabel' => __('app.status.' . ($order->status_key ?? 'pending')),
                'createdAt' => $order->created_at,
                'updatedAt' => $order->updated_at,
            ];
        })->values();

        return response()->json([
            'orders' => $orders,
            'recordsFiltered' => $recordsFiltered,
            'page' => $page,
            'perPage' => $perPage,
            'filters' => [
                'status' => $status,
                'statusOptions' => array_map(fn ($key, $label) => ['key' => $key, 'label' => $label], array_keys($statusOptions), array_values($statusOptions)),
                'unit' => $unit > 0 ? (string) $unit : null,
                'unitOptions' => $unitOptions->map(fn ($u) => ['id' => (string) $u->id, 'value' => $u->value])->values(),
            ],
        ]);
    }

    public function show(Request $request, string $id)
    {
        $admin = $request->attributes->get('admin');
        $role = $this->role($request);

        $order = $this->adminOrders->findOrderDetail((int) $id);
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if (!$this->adminRoles->canSeeOrderStatus($role, $order->status_key ?? null)) {
            return response()->json(['message' => 'This order is not in your queue yet.'], 403);
        }

        $orderedClothes = DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get();

        $orderUniforms = app(OrderUniformService::class);
        $lineUniforms = $orderUniforms->uniforms($orderedClothes->map(fn ($line) => (int) ($line->uniforms_id ?? 0) ?: (int) $order->uniforms_id)->all());

        $items = $orderedClothes->map(function ($line) use ($order, $orderUniforms, $lineUniforms) {
            $lineUniformId = (int) ($line->uniforms_id ?? 0) ?: (int) $order->uniforms_id;
            return [
                'id' => (string) $line->id,
                'clothes' => $line->clothes,
                'size' => $line->size,
                'quantity' => (int) ($line->quantity ?? 1),
                'approvedQuantity' => isset($line->approved_quantity) ? (int) $line->approved_quantity : null,
                'issueVoucher' => $line->issue_voucher ?? null,
                'uniformLabel' => $orderUniforms->label($lineUniforms[$lineUniformId] ?? null, $lineUniformId),
            ];
        })->values();

        return response()->json([
            'order' => [
                'id' => (string) $order->id,
                'sId' => $order->s_id ?: null,
                'email' => $order->email ?: null,
                'name' => $order->name ?: null,
                'unitName' => $order->unit_name ?: null,
                'uniformLabel' => app(OrderUniformService::class)->labelsByOrder([$order->id])[(int) $order->id] ?? '',
                'status' => $order->status_key ?? 'pending',
                'statusLabel' => __('app.status.' . ($order->status_key ?? 'pending')),
                'remarks' => $order->remarks,
                'collectionDate' => $order->collection_date,
                'createdAt' => $order->created_at,
                'updatedAt' => $order->updated_at,
                'orderReference' => $this->kewPs8OrderReference($order),
            ],
            'items' => $items,
            'allowedStatuses' => $this->adminRoles->allowedOrderStatusCodes($role),
            'canApproveQuantities' => $this->adminOrders->canApproveQuantities($role),
            'canRecordIssueVouchers' => $this->adminOrders->canRecordIssueVouchers($role),
            'isOwnOrder' => $this->adminOrders->isOwnOrder((int) $admin->id, $order),
        ]);
    }

    /**
     * JSON counterpart to AdminController::downloadUniformOrderKewPs8 -- any
     * admin who can see this order's queue can download its KEW.PS-8, same
     * as the Blade page.
     */
    public function kewPs8(Request $request, string $id)
    {
        $role = $this->role($request);

        $order = $this->adminOrders->findOrder((int) $id);
        if (!$order) {
            abort(404);
        }

        if (!$this->adminRoles->canSeeOrderStatus($role, $this->orderStatus->orderStatusMeta($order->status ?? null)['key'])) {
            abort(403);
        }

        $personalDetail = DB::table('personal_details')->where('user_id', '=', $order->user_id)->first();
        $uniform = DB::table('uniforms')->where('id', '=', $order->uniforms_id)->first();
        $items = DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get();

        $pdf = Pdf::loadView('reports.kew_ps8', [
            'order' => $order,
            'uniform' => $uniform,
            'applicantName' => $this->kewPs8SignatoryName($personalDetail),
            'applicantPosition' => $this->kewPs8ApplicantPosition($order->user_id),
            'printedAt' => $this->kewPs8PrintedAt(),
            'orderReference' => $this->kewPs8OrderReference($order),
            'scafReference' => $this->kewPs8ScafReference($order),
            'uniformName' => $this->kewPs8UniformName($uniform),
            'approver' => $this->kewPs8Approver($order),
            'receipt' => $this->kewPs8Receipt($order, $personalDetail),
            'reportForms' => $this->chunkKewPs8Rows($items),
            'forPdf' => true,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('KEW-PS8-' . $this->kewPs8OrderReference($order) . '.pdf');
    }

    public function updateStatus(Request $request, string $id)
    {
        $admin = $request->attributes->get('admin');
        $role = $this->role($request);

        if (!$this->orderStatus->hasOrderLifecycleColumns()) {
            return response()->json(['message' => 'Please run the latest migration before updating order status.'], 500);
        }

        $allowedStatuses = $this->adminRoles->allowedOrderStatusCodes($role);

        $input = $request->all();
        $input['order_id'] = (int) $id;

        $validator = $this->adminOrders->validate($input, $allowedStatuses);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $order = $this->adminOrders->findOrder((int) $id);
        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        try {
            $updated = $this->adminOrders->updateStatus($order, $role, (int) $admin->id, $validator->validated());
        } catch (AdminOrderActionRefusedException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'order' => [
                'id' => (string) $updated->id,
                'status' => $this->orderStatus->orderStatusMeta($updated->status ?? null)['key'],
            ],
        ]);
    }

}
