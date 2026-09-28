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

    /**
     * The page. The rows are loaded by DataTables from data(); the uniform
     * filter decides which columns exist, so changing it reloads the page.
     */
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

        [$uniformFilter, $columns] = $this->uniformColumns($uniformsAll, $request->query('uniform'));
        [$monthFilter, $yearFilter] = $this->period($request->query('month'), $request->query('year'));

        return view('admin/personal_inventory', [
            'columns' => $columns,
            'uniformsAll' => $uniformsAll,
            'years' => $years,
            'months' => self::MONTHS,
            'search' => trim((string) $request->query('search', '')),
            'uniformFilter' => $uniformFilter,
            'monthFilter' => $monthFilter,
            'yearFilter' => $yearFilter,
            'perPage' => self::PER_PAGE,
        ]);
    }

    /**
     * Server-side DataTables feed. One row per member, one count per uniform
     * column, then Orders and Total. Search, sort and paging are bound or
     * matched against a fixed list, never pasted into SQL.
     */
    public function data(Request $request)
    {
        $uniformsAll = DB::table('uniforms')->orderBy('id')->get();
        [$uniformFilter, $columns] = $this->uniformColumns($uniformsAll, $request->input('uniform'));
        [$monthFilter, $yearFilter] = $this->period($request->input('month'), $request->input('year'));

        // Every count for every member in one grouped pass, so each column --
        // the per-uniform ones included -- can be sorted on. An order placed
        // before the quantity column existed is one piece.
        $itemExpr = 'COALESCE(ordered_clothes.quantity, 1)';
        $inventory = DB::table('orders')
            ->leftJoin('ordered_clothes', 'ordered_clothes.order_id', '=', 'orders.id')
            ->where('orders.deleted', '=', 0)
            ->groupBy('orders.user_id')
            ->selectRaw('orders.user_id as user_id')
            ->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw("COALESCE(SUM($itemExpr), 0) as item_count");
        foreach ($columns as $uniform) {
            $id = (int) $uniform->id;
            $inventory->selectRaw("COALESCE(SUM(CASE WHEN orders.uniforms_id = $id THEN $itemExpr END), 0) as items_$id")
                ->selectRaw("COUNT(DISTINCT CASE WHEN orders.uniforms_id = $id THEN orders.id END) as orders_$id");
        }
        if ($uniformFilter !== 'all') {
            $inventory->where('orders.uniforms_id', '=', (int) $uniformFilter);
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

        // Every member is listed, ordered or not: nothing on order reads 0.
        $query = DB::table('gen_users')
            ->leftJoin('personal_details', 'personal_details.user_id', '=', 'gen_users.id')
            ->leftJoin('units', 'units.id', '=', 'personal_details.unit')
            ->leftJoinSub($inventory, 'inv', 'inv.user_id', '=', 'gen_users.id')
            ->select($select);

        $recordsTotal = DB::table('gen_users')->count();

        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('gen_users.s_id', 'like', $like)
                    ->orWhere('personal_details.name', 'like', $like)
                    ->orWhere('units.value', 'like', $like);
            });
        }
        $recordsFiltered = (clone $query)->count();

        // Sortable columns by their index in the table: Service ID, Name,
        // Unit, one per uniform, then Orders and Total.
        $sortColumns = ['gen_users.s_id', 'personal_details.name', 'units.value'];
        foreach ($columns as $uniform) {
            $sortColumns[] = 'items_' . (int) $uniform->id;
        }
        $sortColumns[] = 'order_count';
        $sortColumns[] = 'item_count';

        $sortIndex = (int) $request->input('order.0.column', 0);
        $sortDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortColumns[$sortIndex] ?? $sortColumns[0], $sortDir)->orderBy('gen_users.s_id');

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', self::PER_PAGE);
        $length = $length < 1 ? 500 : min($length, 500);

        // The chosen period follows the member into the detail page.
        $detailParams = array_filter([
            'month' => $monthFilter === 'all' ? null : $monthFilter,
            'year' => $yearFilter === 'all' ? null : $yearFilter,
        ]);

        $rows = [];
        foreach ($query->offset($start)->limit($length)->get() as $member) {
            $detailUrl = $member->s_id
                ? route('admin.personal-inventory.show', array_merge(['sId' => $member->s_id], $detailParams))
                : null;

            $row = [
                $detailUrl ? '<a href="' . e($detailUrl) . '" target="_blank" rel="noopener">' . e($member->s_id) . '</a>' : '-',
                e($member->name ?: 'N/A'),
                e($member->unit_name ?: 'N/A'),
            ];
            foreach ($columns as $uniform) {
                $id = (int) $uniform->id;
                $row[] = $this->countCell((int) $member->{"items_$id"},
                    ($uniform->uniform_name ?: $uniform->uniform_type) . ': ' . (int) $member->{"items_$id"} . ' item(s) across ' . (int) $member->{"orders_$id"} . ' order(s)');
            }
            $row[] = $this->countCell((int) $member->order_count);
            $row[] = $this->countCell((int) $member->item_count);
            $row['DT_RowAttr'] = $detailUrl ? ['data-href' => $detailUrl] : [];
            $row['DT_RowClass'] = 'inventory-row';
            $rows[] = $row;
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    /**
     * A count, with the tooltip saying what it is made of. The table styles
     * the cell itself (centred, 0 muted).
     */
    private function countCell(int $count, ?string $title = null): string
    {
        return $title !== null ? '<span title="' . e($title) . '">' . $count . '</span>' : (string) $count;
    }

    /**
     * The uniform filter and the uniform columns it leaves on the table. An
     * unknown uniform id falls back to every type rather than an empty table.
     */
    private function uniformColumns($uniformsAll, $requested): array
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

    /**
     * Month and year filters, each 'all' unless it names a real month or year.
     */
    private function period($month, $year): array
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
}
