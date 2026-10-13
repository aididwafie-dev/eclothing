<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminPersonalInventoryService;
use Illuminate\Http\Request;

/**
 * JSON counterpart to AdminPersonalInventoryController, for the admin side
 * of the Next.js UI. Open to both admin roles -- Personal Inventory is on
 * EnsureAdminRoleAllowsRoute's "orders" allowlist in the web app.
 */
class PersonalInventoryController extends Controller
{
    private const PER_PAGE = 25;

    public function __construct(private AdminPersonalInventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        $uniformsAll = $this->inventory->allUniforms();
        [$uniformFilter, $columns] = $this->inventory->uniformColumns($uniformsAll, $request->query('uniform'));
        [$monthFilter, $yearFilter] = $this->inventory->period($request->query('month'), $request->query('year'));

        $sortColumnCount = 3 + $columns->count() + 2;
        $sortIndex = (int) $request->query('sortColumn', 0);
        $sortIndex = $sortIndex >= 0 && $sortIndex < $sortColumnCount ? $sortIndex : 0;
        $sortDir = strtolower((string) $request->query('sortDir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(500, max(1, (int) $request->query('perPage', self::PER_PAGE)));

        $result = $this->inventory->listRows(
            $columns,
            $uniformFilter,
            $monthFilter,
            $yearFilter,
            trim((string) $request->query('search', '')),
            $sortIndex,
            $sortDir,
            ($page - 1) * $perPage,
            $perPage
        );

        $rows = $result['rows']->map(function ($member) use ($columns) {
            $uniformCounts = [];
            foreach ($columns as $uniform) {
                $id = (int) $uniform->id;
                $uniformCounts[] = [
                    'uniformId' => (string) $id,
                    'label' => $uniform->uniform_name ?: $uniform->uniform_type,
                    'items' => (int) $member->{"items_$id"},
                    'orders' => (int) $member->{"orders_$id"},
                ];
            }

            return [
                'sId' => $member->s_id,
                'name' => $member->name ?: null,
                'unitName' => $member->unit_name ?: null,
                'uniformCounts' => $uniformCounts,
                'orderCount' => (int) $member->order_count,
                'itemCount' => (int) $member->item_count,
            ];
        })->values();

        return response()->json([
            'rows' => $rows,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'page' => $page,
            'perPage' => $perPage,
            'filters' => [
                'uniform' => $uniformFilter,
                'uniformOptions' => $uniformsAll->map(fn ($u) => ['id' => (string) $u->id, 'label' => $u->uniform_name ?: $u->uniform_type])->values(),
                'month' => $monthFilter,
                'monthOptions' => array_map(fn ($k, $v) => ['value' => (string) $k, 'label' => $v], array_keys(AdminPersonalInventoryService::MONTHS), array_values(AdminPersonalInventoryService::MONTHS)),
                'year' => $yearFilter,
                'yearOptions' => $this->inventory->yearsWithOrders()->values(),
            ],
        ]);
    }

    public function show(Request $request, string $sId)
    {
        $monthFilter = (string) $request->query('month', 'all');
        $yearFilter = (string) $request->query('year', 'all');

        $detail = $this->inventory->memberDetail($sId, $monthFilter, $yearFilter);
        if (!$detail) {
            return response()->json(['message' => 'Member not found.'], 404);
        }

        $groups = [];
        foreach ($detail['groups'] as $uniformId => $group) {
            $groups[] = [
                'uniformId' => (string) $uniformId,
                'label' => $group['label'],
                'orderCount' => $group['orderCount'],
                'itemCount' => $group['itemCount'],
                'orders' => array_map(function ($entry) {
                    $order = $entry['order'];
                    return [
                        'id' => (string) $order->id,
                        'status' => $entry['status']['key'],
                        'statusLabel' => $entry['status']['label'],
                        'createdAt' => $order->created_at,
                        'collectionDate' => $order->collection_date,
                        'remarks' => $order->remarks,
                        'itemCount' => $entry['itemCount'],
                        'items' => $entry['items']->map(fn ($i) => [
                            'clothes' => $i->clothes,
                            'size' => $i->size,
                            'quantity' => max(1, (int) ($i->quantity ?? 1)),
                        ])->values(),
                    ];
                }, $group['orders']),
            ];
        }

        return response()->json([
            'member' => [
                'sId' => $detail['member']->s_id,
                'name' => $detail['member']->name ?: null,
                'unitName' => $detail['member']->unit_name ?: null,
                'rankName' => $detail['member']->rank_name ?: null,
            ],
            'groups' => $groups,
            'orderTotal' => $detail['orderTotal'],
            'itemTotal' => $detail['itemTotal'],
            'filters' => [
                'month' => $monthFilter,
                'year' => $yearFilter,
                'yearOptions' => $detail['years']->values(),
            ],
        ]);
    }
}
