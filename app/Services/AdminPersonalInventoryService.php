<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Personal Inventory's queries, extracted out of
 * AdminPersonalInventoryController so the Blade page's DataTables feed and
 * the Api\Admin\PersonalInventoryController JSON endpoint build their rows
 * from the exact same data, one presented as HTML table cells, the other
 * as plain JSON.
 */
class AdminPersonalInventoryService
{
    public const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    public function allUniforms()
    {
        return DB::table('uniforms')->orderBy('id')->get();
    }

    /** Years that actually carry orders, newest first. */
    public function yearsWithOrders(?int $userId = null)
    {
        $query = DB::table('orders')->where('deleted', '=', 0);
        if ($userId !== null) {
            $query->where('user_id', '=', $userId);
        }

        return $query->selectRaw('DISTINCT YEAR(created_at) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->filter(fn ($y) => (int) $y > 0)
            ->values();
    }

    /**
     * The uniform filter and the uniform columns it leaves on the table. An
     * unknown uniform id falls back to every type rather than an empty table.
     *
     * @return array{0: string, 1: \Illuminate\Support\Collection}
     */
    public function uniformColumns($uniformsAll, $requested): array
    {
        $uniformFilter = (string) ($requested ?? 'all');
        if ($uniformFilter !== 'all') {
            $picked = $uniformsAll->firstWhere('id', (int) $uniformFilter);
            if ($picked) {
                return [(string) (int) $picked->id, collect([$picked])];
            }
        }

        return ['all', $uniformsAll];
    }

    /** Month and year filters, each 'all' unless it names a real month or year. */
    public function period($month, $year): array
    {
        $month = (string) ($month ?? 'all');
        if ($month !== 'all' && !isset(self::MONTHS[(int) $month])) {
            $month = 'all';
        }
        $year = (string) ($year ?? 'all');
        if ($year !== 'all' && ((int) $year < 1900 || (int) $year > 2999)) {
            $year = 'all';
        }

        return [$month === 'all' ? 'all' : (string) (int) $month, $year === 'all' ? 'all' : (string) (int) $year];
    }

    /**
     * One row per member (every member, ordered or not), one count per
     * uniform column, then Orders and Total -- plus search/sort/paging.
     * Returns the raw rows (member + uniform-keyed counts) and the total
     * counts; the caller renders them as it needs.
     *
     * @param  \Illuminate\Support\Collection $columns the uniform columns in scope
     * @return array{rows: \Illuminate\Support\Collection, recordsTotal: int, recordsFiltered: int}
     */
    public function listRows(
        $columns,
        string $uniformFilter,
        string $monthFilter,
        string $yearFilter,
        string $search,
        int $sortIndex,
        string $sortDir,
        int $start,
        int $length
    ): array {
        $itemExpr = 'COALESCE(ordered_clothes.quantity, 1)';
        $inventory = DB::table('orders')
            ->leftJoin('ordered_clothes', 'ordered_clothes.order_id', '=', 'orders.id')
            ->where('orders.deleted', '=', 0)
            ->groupBy('orders.user_id')
            ->selectRaw('orders.user_id as user_id')
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw("COALESCE(SUM($itemExpr), 0) as item_count");

        $lineUniform = OrderUniformService::LINE_UNIFORM_SQL;
        foreach ($columns as $uniform) {
            $id = (int) $uniform->id;
            $inventory->selectRaw("COALESCE(SUM(CASE WHEN $lineUniform = $id THEN $itemExpr END), 0) as items_$id")
                ->selectRaw("COUNT(DISTINCT CASE WHEN $lineUniform = $id THEN orders.id END) as orders_$id");
        }
        if ($uniformFilter !== 'all') {
            $inventory->whereRaw("$lineUniform = ?", [(int) $uniformFilter]);
        }
        if ($yearFilter !== 'all') {
            $inventory->whereYear('orders.created_at', (int) $yearFilter);
        }
        if ($monthFilter !== 'all') {
            $inventory->whereMonth('orders.created_at', (int) $monthFilter);
        }

        $select = ['gen_users.id', 'gen_users.s_id', 'personal_details.name', 'units.value as unit_name',
            DB::raw('COALESCE(inv.order_count, 0) as order_count'), DB::raw('COALESCE(inv.item_count, 0) as item_count')];
        foreach ($columns as $uniform) {
            $id = (int) $uniform->id;
            $select[] = DB::raw("COALESCE(inv.items_$id, 0) as items_$id");
            $select[] = DB::raw("COALESCE(inv.orders_$id, 0) as orders_$id");
        }

        $query = DB::table('gen_users')
            ->leftJoin('personal_details', 'personal_details.user_id', '=', 'gen_users.id')
            ->leftJoin('units', 'units.id', '=', 'personal_details.unit')
            ->leftJoinSub($inventory, 'inv', 'inv.user_id', '=', 'gen_users.id')
            ->select($select);

        $recordsTotal = DB::table('gen_users')->count();

        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('gen_users.s_id', 'like', $like)
                    ->orWhere('personal_details.name', 'like', $like)
                    ->orWhere('units.value', 'like', $like);
            });
        }
        $recordsFiltered = (clone $query)->count();

        $sortColumns = ['gen_users.s_id', 'personal_details.name', 'units.value'];
        foreach ($columns as $uniform) {
            $sortColumns[] = 'items_' . (int) $uniform->id;
        }
        $sortColumns[] = 'order_count';
        $sortColumns[] = 'item_count';

        $query->orderBy($sortColumns[$sortIndex] ?? $sortColumns[0], $sortDir)->orderBy('gen_users.s_id');

        $rows = $query->offset($start)->limit($length)->get();

        return ['rows' => $rows, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered];
    }

    /**
     * One member's inventory in full: every uniform they have ordered, the
     * orders behind it and the clothing lines on each, filterable by month
     * and year.
     */
    public function memberDetail(string $sId, string $monthFilter, string $yearFilter): ?array
    {
        $member = DB::table('gen_users')
            ->leftJoin('personal_details', 'personal_details.user_id', '=', 'gen_users.id')
            ->leftJoin('units', 'units.id', '=', 'personal_details.unit')
            ->leftJoin('pangkats', 'pangkats.id', '=', 'personal_details.pangkat')
            ->where('gen_users.s_id', '=', $sId)
            ->select(
                'gen_users.id',
                'gen_users.s_id',
                'personal_details.name',
                'units.value as unit_name',
                'pangkats.value as rank_name'
            )
            ->first();

        if (!$member) {
            return null;
        }

        $years = $this->yearsWithOrders((int) $member->id);

        $ordersQuery = DB::table('orders')
            ->leftJoin('uniforms', 'uniforms.id', '=', 'orders.uniforms_id')
            ->where('orders.user_id', '=', $member->id)
            ->where('orders.deleted', '=', 0)
            ->select(
                'orders.id',
                'orders.uniforms_id',
                'orders.status',
                'orders.created_at',
                'orders.collection_date',
                'orders.remarks',
                'uniforms.uniform_type',
                'uniforms.uniform_name'
            )
            ->orderByDesc('orders.created_at');

        if ($yearFilter !== 'all') {
            $ordersQuery->whereYear('orders.created_at', (int) $yearFilter);
        }
        if ($monthFilter !== 'all') {
            $ordersQuery->whereMonth('orders.created_at', (int) $monthFilter);
        }

        $orders = $ordersQuery->get();

        $itemsByOrder = $orders->isEmpty()
            ? collect()
            : DB::table('ordered_clothes')
                ->whereIn('order_id', $orders->pluck('id')->all())
                ->orderBy('clothes')
                ->get()
                ->groupBy(fn ($item) => (int) $item->order_id);

        $uniformsById = app(OrderUniformService::class)->uniforms(
            $itemsByOrder->flatten(1)->map(fn ($item) => (int) ($item->uniforms_id ?? 0))
                ->merge($orders->pluck('uniforms_id')->map(fn ($id) => (int) $id))->all()
        );

        $groups = [];
        foreach ($orders as $order) {
            $orderItems = $itemsByOrder->get((int) $order->id, collect());
            $byUniform = $orderItems->isEmpty()
                ? collect([(int) $order->uniforms_id => collect()])
                : $orderItems->groupBy(fn ($item) => (int) ($item->uniforms_id ?? 0) ?: (int) $order->uniforms_id);

            foreach ($byUniform as $uniformId => $items) {
                $uniformId = (int) $uniformId;
                $itemCount = (int) $items->sum(fn ($item) => max(1, (int) ($item->quantity ?? 1)));
                $uniform = $uniformsById[$uniformId] ?? null;

                if (!isset($groups[$uniformId])) {
                    $groups[$uniformId] = [
                        'label' => $uniform
                            ? $uniform->uniform_type . ($uniform->uniform_name ? ' - ' . $uniform->uniform_name : '')
                            : 'Uniform #' . $uniformId,
                        'orders' => [],
                        'orderCount' => 0,
                        'itemCount' => 0,
                    ];
                }

                $groups[$uniformId]['orders'][] = [
                    'order' => $order,
                    'items' => $items,
                    'itemCount' => $itemCount,
                    'status' => app(OrderStatusService::class)->orderStatusMeta($order->status ?? null),
                ];
                $groups[$uniformId]['orderCount']++;
                $groups[$uniformId]['itemCount'] += $itemCount;
            }
        }

        return [
            'member' => $member,
            'groups' => $groups,
            'orderTotal' => $orders->count(),
            'itemTotal' => array_sum(array_column($groups, 'itemCount')),
            'years' => $years,
        ];
    }
}
