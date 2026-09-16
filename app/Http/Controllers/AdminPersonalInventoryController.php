<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Personal Inventory: what each member holds on order, one row per member
 * and one column per uniform type.
 *
 * Every member is listed whether or not they have ordered anything -- a
 * missing combination reads as 0 rather than vanishing, because "this person
 * has none of that uniform" is the answer the store is usually looking for.
 *
 * Counts are of items (the pieces on the order, honouring quantity), with the
 * number of orders behind each cell's tooltip.
 */
class AdminPersonalInventoryController extends Controller
{
    use \App\Http\Controllers\Concerns\ResolvesOrderStatus;

    private const PER_PAGE = 25;

    public const MONTHS = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    public function index(Request $request)
    {
        $uniformsAll = DB::table('uniforms')->orderBy('id')->get();

        // Only years that actually carry orders, newest first.
        $years = DB::table('orders')
            ->where('deleted', '=', 0)
            ->selectRaw('DISTINCT YEAR(created_at) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->filter(fn ($y) => (int) $y > 0)
            ->values();

        $search = trim((string) $request->query('search', ''));
        $uniformFilter = (string) $request->query('uniform', 'all');
        $monthFilter = (string) $request->query('month', 'all');
        $yearFilter = (string) $request->query('year', 'all');

        // An unknown uniform id falls back to showing every type rather than
        // an empty table.
        $columns = $uniformsAll;
        if ($uniformFilter !== 'all') {
            $picked = $uniformsAll->firstWhere('id', (int) $uniformFilter);
            if ($picked) {
                $columns = collect([$picked]);
            } else {
                $uniformFilter = 'all';
            }
        }

        $membersQuery = DB::table('gen_users')
            ->leftJoin('personal_details', 'personal_details.user_id', '=', 'gen_users.id')
            ->leftJoin('units', 'units.id', '=', 'personal_details.unit')
            ->select(
                'gen_users.id',
                'gen_users.s_id',
                'personal_details.name',
                'units.value as unit_name'
            )
            ->orderBy('gen_users.s_id');

        if ($search !== '') {
            $membersQuery->where('gen_users.s_id', 'like', '%' . $search . '%');
        }

        $members = $membersQuery->paginate(self::PER_PAGE)->withQueryString();

        $totals = $this->totalsFor(
            collect($members->items())->pluck('id')->all(),
            $uniformFilter,
            $monthFilter,
            $yearFilter
        );

        return view('admin/personal_inventory', [
            'members' => $members,
            'columns' => $columns,
            'uniformsAll' => $uniformsAll,
            'totals' => $totals,
            'years' => $years,
            'months' => self::MONTHS,
            'search' => $search,
            'uniformFilter' => $uniformFilter,
            'monthFilter' => $monthFilter,
            'yearFilter' => $yearFilter,
        ]);
    }

    /**
     * One member's inventory in full: every uniform they have ordered, the
     * orders behind it and the clothing lines on each, filterable by month
     * and year. Addressed by Service ID because that is what identifies a
     * member on the list page and in the store's paperwork.
     */
    public function show(Request $request, string $sId)
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
            abort(404);
        }

        $monthFilter = (string) $request->query('month', 'all');
        $yearFilter = (string) $request->query('year', 'all');

        // Years this member actually ordered in, so the dropdown never offers
        // a period that cannot hold anything.
        $years = DB::table('orders')
            ->where('deleted', '=', 0)
            ->where('user_id', '=', $member->id)
            ->selectRaw('DISTINCT YEAR(created_at) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->filter(fn ($y) => (int) $y > 0)
            ->values();

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

        // One query for every line on this member's orders, rather than one
        // per order.
        $itemsByOrder = $orders->isEmpty()
            ? collect()
            : DB::table('ordered_clothes')
                ->whereIn('order_id', $orders->pluck('id')->all())
                ->orderBy('clothes')
                ->get()
                ->groupBy(fn ($item) => (int) $item->order_id);

        $groups = [];
        foreach ($orders as $order) {
            $items = $itemsByOrder->get((int) $order->id, collect());
            $itemCount = (int) $items->sum(fn ($item) => max(1, (int) ($item->quantity ?? 1)));
            $uniformId = (int) $order->uniforms_id;

            if (!isset($groups[$uniformId])) {
                $groups[$uniformId] = [
                    'label' => $order->uniform_type
                        ? $order->uniform_type . ($order->uniform_name ? ' - ' . $order->uniform_name : '')
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
                'status' => $this->orderStatus()->orderStatusMeta($order->status ?? null),
            ];
            $groups[$uniformId]['orderCount']++;
            $groups[$uniformId]['itemCount'] += $itemCount;
        }

        return view('admin/personal_inventory_detail', [
            'member' => $member,
            'groups' => $groups,
            'orderTotal' => $orders->count(),
            'itemTotal' => array_sum(array_column($groups, 'itemCount')),
            'years' => $years,
            'months' => self::MONTHS,
            'monthFilter' => $monthFilter,
            'yearFilter' => $yearFilter,
        ]);
    }

    /**
     * Item and order counts for the members on this page, keyed by member id
     * then uniform id. Only the listed members are queried, so the cost does
     * not grow with the size of the roll.
     *
     * @param  array<int, int|string> $userIds
     * @return array<int, array<int, array{orders: int, items: int}>>
     */
    private function totalsFor(array $userIds, string $uniformFilter, string $monthFilter, string $yearFilter): array
    {
        if (!$userIds) {
            return [];
        }

        $query = DB::table('orders')
            ->leftJoin('ordered_clothes', 'ordered_clothes.order_id', '=', 'orders.id')
            ->whereIn('orders.user_id', $userIds)
            ->where('orders.deleted', '=', 0)
            ->selectRaw('orders.user_id as uid, orders.uniforms_id as uniform_id')
            // An order placed before the quantity column existed is one piece.
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('COALESCE(SUM(COALESCE(ordered_clothes.quantity, 1)), 0) as item_count')
            ->groupBy('orders.user_id', 'orders.uniforms_id');

        if ($uniformFilter !== 'all') {
            $query->where('orders.uniforms_id', '=', $uniformFilter);
        }
        if ($yearFilter !== 'all') {
            $query->whereYear('orders.created_at', (int) $yearFilter);
        }
        if ($monthFilter !== 'all') {
            $query->whereMonth('orders.created_at', (int) $monthFilter);
        }

        $totals = [];
        foreach ($query->get() as $row) {
            $totals[(int) $row->uid][(int) $row->uniform_id] = [
                'orders' => (int) $row->order_count,
                'items' => (int) $row->item_count,
            ];
        }

        return $totals;
    }
}
