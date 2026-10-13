<?php

namespace App\Http\Controllers;

use App\Services\AdminPersonalInventoryService;
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
 *
 * The actual queries live in AdminPersonalInventoryService, shared with
 * Api\Admin\PersonalInventoryController.
 */
class AdminPersonalInventoryController extends Controller
{
    private const PER_PAGE = 25;

    public const MONTHS = AdminPersonalInventoryService::MONTHS;

    public function index(Request $request)
    {
        $service = app(AdminPersonalInventoryService::class);
        $uniformsAll = $service->allUniforms();
        $years = $service->yearsWithOrders();

        [$uniformFilter, $columns] = $service->uniformColumns($uniformsAll, $request->query('uniform'));
        [$monthFilter, $yearFilter] = $service->period($request->query('month'), $request->query('year'));

        return view('admin/personal_inventory', [
            'columns' => $columns,
            'uniformsAll' => $uniformsAll,
            'years' => $years,
            'months' => AdminPersonalInventoryService::MONTHS,
            'search' => trim((string) $request->query('search', '')),
            'uniformFilter' => $uniformFilter,
            'monthFilter' => $monthFilter,
            'yearFilter' => $yearFilter,
            'perPage' => self::PER_PAGE,
        ]);
    }

    public function data(Request $request)
    {
        $service = app(AdminPersonalInventoryService::class);
        $uniformsAll = $service->allUniforms();
        [$uniformFilter, $columns] = $service->uniformColumns($uniformsAll, $request->input('uniform'));
        [$monthFilter, $yearFilter] = $service->period($request->input('month'), $request->input('year'));

        $sortColumnCount = 3 + $columns->count() + 2;
        $sortIndex = (int) $request->input('order.0.column', 0);
        $sortIndex = $sortIndex >= 0 && $sortIndex < $sortColumnCount ? $sortIndex : 0;
        $sortDir = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', self::PER_PAGE);
        $length = $length < 1 ? 500 : min($length, 500);

        $result = $service->listRows(
            $columns,
            $uniformFilter,
            $monthFilter,
            $yearFilter,
            trim((string) $request->input('search.value', '')),
            $sortIndex,
            $sortDir,
            $start,
            $length
        );

        // The chosen period follows the member into the detail page.
        $detailParams = array_filter([
            'month' => $monthFilter === 'all' ? null : $monthFilter,
            'year' => $yearFilter === 'all' ? null : $yearFilter,
        ]);

        $rows = [];
        foreach ($result['rows'] as $member) {
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
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $rows,
        ]);
    }

    private function countCell(int $count, ?string $title = null): string
    {
        return $title !== null ? '<span title="' . e($title) . '">' . $count . '</span>' : (string) $count;
    }

    public function show(Request $request, string $sId)
    {
        $service = app(AdminPersonalInventoryService::class);
        $monthFilter = (string) $request->query('month', 'all');
        $yearFilter = (string) $request->query('year', 'all');

        $detail = $service->memberDetail($sId, $monthFilter, $yearFilter);
        if (!$detail) {
            abort(404);
        }

        return view('admin/personal_inventory_detail', [
            'member' => $detail['member'],
            'groups' => $detail['groups'],
            'orderTotal' => $detail['orderTotal'],
            'itemTotal' => $detail['itemTotal'],
            'years' => $detail['years'],
            'months' => AdminPersonalInventoryService::MONTHS,
            'monthFilter' => $monthFilter,
            'yearFilter' => $yearFilter,
        ]);
    }
}
