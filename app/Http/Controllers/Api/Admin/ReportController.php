<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminUsersReportController;
use App\Http\Controllers\Controller;
use App\Services\OrderUniformService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * JSON counterpart to AdminReportController (Order Reports) and
 * AdminUsersReportController (User Reports), for the admin side of the
 * Next.js UI. The size-count/user-detail helpers and every Excel export are
 * the web controllers' own methods, called as-is so the numbers and the
 * spreadsheets stay identical. Superadmin-only.
 */
class ReportController extends Controller
{
    public function __construct(
        private AdminReportController $orderReports,
        private AdminUsersReportController $userReports,
        private OrderUniformService $orderUniforms,
    ) {
    }

    /** The uniform/unit pickers on every "Generate Report" form. */
    public function options()
    {
        return response()->json([
            'uniforms' => DB::table('uniforms')->where('active', 1)->get()->map(fn ($u) => [
                'id' => (string) $u->id,
                'value' => $this->uniformLabel($u),
            ])->values(),
            'units' => DB::table('units')->get()
                ->map(fn ($u) => ['id' => (string) $u->id, 'value' => $u->value])->values(),
        ]);
    }

    /** loadClothAjax -- the cloth picker of the cloth-wise report. */
    public function clothes(Request $request)
    {
        return response()->json([
            'clothes' => DB::table('uniform_clothes')->where('uniforms_id', '=', (int) $request->query('uniformId'))->get()
                ->map(fn ($c) => ['id' => $c->clothes_slug, 'value' => $c->clothes_type])->values(),
        ]);
    }

    // --- Order reports ----------------------------------------------------

    /** getReportUniformWise. */
    public function uniform(Request $request)
    {
        $uniform = $this->findUniform($request);
        if (!$uniform) {
            return $this->notFound('Uniform');
        }

        $hasOrders = $this->orderUniforms->whereHasUniform(DB::table('orders')->where('deleted', '=', 0), (int) $uniform->id)->exists();
        $groups = $hasOrders ? $this->orderReports->orderedSizeCountUniformwise($uniform->id) : [];

        return response()->json([
            'uniform' => $this->uniformLabel($uniform),
            'uniformType' => $uniform->uniform_type,
            'clothes' => count($groups),
            // The Blade page's "Total order" summed the first cloth's sizes only.
            'totalOrders' => array_sum(array_column($groups[0] ?? [], 'count')),
            'rows' => collect($groups)->flatten(1)->map(fn ($c) => [
                'clothes' => $c['orders']->clothes,
                'size' => $c['orders']->size,
                'count' => $c['count'],
            ])->values(),
        ]);
    }

    /** getReportUserWise. */
    public function uniformUsers(Request $request)
    {
        $uniform = $this->findUniform($request);
        if (!$uniform) {
            return $this->notFound('Uniform');
        }

        $details = $this->orderReports->orderDetailsWithUserDetails($uniform->id) ?: [];

        $rows = collect($details)->map(fn ($d) => [
            'sortKey' => intval(str_pad($d['user_details']->s_id, 8, '0', STR_PAD_RIGHT)),
            'sId' => $d['user_details']->s_id,
            'rank' => $d['rank']->value ?? '',
            'name' => $d['user_details']->name,
            'unit' => $d['unit']->value ?? '-',
            'sizes' => collect($d['cloth_details'])->pluck('size')->values(),
        ])->sortBy([['sortKey', 'asc'], ['rank', 'asc']])->values()
            ->map(fn ($r) => collect($r)->except('sortKey'));

        return response()->json([
            'uniform' => $this->uniformLabel($uniform),
            'clothes' => DB::table('uniform_clothes')->where('uniforms_id', '=', $uniform->id)->pluck('clothes_type'),
            'rows' => $rows,
        ]);
    }

    /** getReportUserWiseUnit. */
    public function unitUsers(Request $request)
    {
        $unit = $this->findUnit($request);
        if (!$unit) {
            return $this->notFound('Unit');
        }

        $users = DB::table('personal_details')
            ->leftJoin('pangkats', 'pangkats.id', '=', 'personal_details.pangkat')
            ->where('unit', '=', $unit->id)
            ->orderBy('pangkats_order', 'asc')
            ->orderBy('s_id', 'asc')
            ->select('personal_details.*', 'pangkats.value as rank_value')
            ->get();

        $uniforms = DB::table('uniforms')->where('active', 1)->get();
        $ordered = $this->orderedUniformsByUser($users->pluck('user_id')->all());

        return response()->json([
            'unit' => $unit->value,
            'uniforms' => $uniforms->pluck('uniform_type')->values(),
            'rows' => $users->map(fn ($u) => [
                'sId' => $u->s_id,
                'rank' => $u->rank_value ?? '',
                'name' => $u->name,
                'ordered' => $uniforms->map(fn ($uni) => isset($ordered[$u->user_id][$uni->id]))->values(),
            ])->values(),
        ]);
    }

    /** getReportunitWise. */
    public function unit(Request $request)
    {
        $unit = $this->findUnit($request);
        if (!$unit) {
            return $this->notFound('Unit');
        }

        $userIds = DB::table('personal_details')->where('unit', '=', $unit->id)->pluck('user_id')->all();
        $orders = $this->orderUniforms->expandByUniform(DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userIds)->get());
        $names = DB::table('uniforms')->where('active', 1)->pluck('uniform_type', 'id');

        return response()->json([
            'unit' => $unit->value,
            'rows' => $orders->countBy('uniforms_id')->map(fn ($count, $uniformId) => [
                'uniform' => $names[$uniformId] ?? '-',
                'count' => $count,
            ])->values(),
            'total' => $orders->count(),
        ]);
    }

    /**
     * getReportUniformUnitWise. The Blade page never rendered (its join had
     * no ON clause and it printed an undefined total), so this is what it set
     * out to show: the uniform-wise size counts, limited to one unit's users.
     */
    public function uniformUnit(Request $request)
    {
        $uniform = $this->findUniform($request);
        $unit = $this->findUnit($request);
        if (!$uniform || !$unit) {
            return $this->notFound($uniform ? 'Unit' : 'Uniform');
        }

        $userIds = DB::table('personal_details')->where('unit', '=', $unit->id)->pluck('user_id')->all();
        $orderIds = $this->orderUniforms->whereHasUniform(
            DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userIds),
            (int) $uniform->id
        )->pluck('orders.id')->all();

        $rows = DB::table('ordered_clothes')
            ->join('uniform_clothes', 'uniform_clothes.clothes_slug', '=', 'ordered_clothes.clothes_slug')
            ->where('uniform_clothes.uniforms_id', '=', $uniform->id)
            ->whereIn('ordered_clothes.order_id', $orderIds)
            ->groupBy('ordered_clothes.clothes', 'ordered_clothes.size')
            ->orderBy('ordered_clothes.clothes')
            ->selectRaw('ordered_clothes.clothes as clothes, ordered_clothes.size as size, COUNT(*) as count')
            ->get();

        return response()->json([
            'uniform' => $this->uniformLabel($uniform),
            'uniformType' => $uniform->uniform_type,
            'unit' => $unit->value,
            'clothes' => $rows->pluck('clothes')->unique()->count(),
            'totalOrders' => count($orderIds),
            'rows' => $rows->map(fn ($r) => ['clothes' => $r->clothes, 'size' => $r->size, 'count' => (int) $r->count])->values(),
        ]);
    }

    /** getReportUniformTag (the cloth-wise order report). */
    public function cloth(Request $request)
    {
        $uniform = $this->findUniform($request);
        if (!$uniform) {
            return $this->notFound('Uniform');
        }

        $slug = (string) $request->query('slug');
        $sizes = $this->orderReports->orderedSizeCountClothwise($slug);

        return response()->json([
            'uniform' => $this->uniformLabel($uniform),
            'uniformType' => $uniform->uniform_type,
            'clothes' => $sizes[0]['orders']->clothes ?? null,
            'rows' => collect($sizes)->map(fn ($c) => ['size' => $c['orders']->size, 'count' => $c['count']])->values(),
            'total' => array_sum(array_column($sizes, 'count')),
        ]);
    }

    // --- User reports -----------------------------------------------------

    /** AdminUsersReportController::getReportUnitWise. */
    public function usersByUnit()
    {
        $rows = DB::table('units')->get()->map(function ($unit) {
            $total = DB::table('personal_details')->where('unit', '=', $unit->id)->count();
            $withOrder = DB::table('orders')
                ->leftJoin('personal_details', 'personal_details.user_id', '=', 'orders.user_id')
                ->where('deleted', '=', 0)
                ->where('personal_details.unit', '=', $unit->id)
                ->distinct()
                ->count('orders.user_id');

            return ['unit' => $unit->value, 'withOrder' => $withOrder, 'withoutOrder' => $total - $withOrder];
        });

        return response()->json(['rows' => $rows->values()]);
    }

    /** AdminUsersReportController::getReportUniformTag. */
    public function uniformTags(Request $request)
    {
        $users = DB::table('gen_users')
            ->leftJoin('personal_details', 'gen_users.id', '=', 'personal_details.user_id')
            ->leftJoin('pangkats', 'pangkats.id', '=', 'personal_details.pangkat')
            ->leftJoin('units', 'units.id', '=', 'personal_details.unit')
            ->where('status', '=', 1)
            ->where('name_tag', '!=', '')
            ->whereNotNull('name_tag')
            ->select('gen_users.s_id', 'personal_details.name', 'personal_details.name_tag', 'pangkats.value as rank_value', 'units.value as unit_value');

        if ($unitId = (int) $request->query('unitId')) {
            $users->where('personal_details.unit', '=', $unitId);
        }
        if ($officerRecruit = (int) $request->query('officerRecruit')) {
            $users->where('pangkats.officer_recruit', '=', $officerRecruit);
        }

        return response()->json([
            'rows' => $users->get()->filter(fn ($u) => $u->s_id)->map(fn ($u) => [
                'sId' => $u->s_id,
                'rank' => $u->rank_value,
                'name' => $u->name,
                'unit' => $u->unit_value,
                'nameTag' => $u->name_tag,
            ])->values(),
        ]);
    }

    /** AdminUsersReportController::getReportWithoutOrders. */
    public function withoutOrders(Request $request)
    {
        $users = DB::table('gen_users')
            ->leftJoin('personal_details', 'gen_users.id', '=', 'personal_details.user_id')
            ->leftJoin('units', 'units.id', '=', 'personal_details.unit')
            ->where('status', '=', 1)
            ->whereNotNull('gen_users.s_id')
            ->where('gen_users.s_id', '!=', '')
            ->whereNotExists(fn ($q) => $q->from('orders')->whereColumn('orders.user_id', 'gen_users.id'))
            ->select('gen_users.s_id', 'gen_users.created_at', 'gen_users.updated_at', 'personal_details.name', 'personal_details.telephone_number', 'units.value as unit_value');

        if ($unitId = (int) $request->query('unitId')) {
            $users->where('personal_details.unit', '=', $unitId);
        }

        return response()->json([
            'rows' => $users->get()->map(fn ($u) => [
                'sId' => $u->s_id,
                'name' => $u->name,
                'telephone' => $u->telephone_number,
                'unit' => $u->unit_value,
                'createdAt' => $u->created_at,
                'updatedAt' => $u->updated_at,
            ])->values(),
        ]);
    }

    /** AdminUsersReportController::getReportStrengthUnits. */
    public function strength()
    {
        $users = DB::table('gen_users')
            ->leftJoin('personal_details', 'gen_users.id', '=', 'personal_details.user_id')
            ->leftJoin('pangkats', 'pangkats.id', '=', 'personal_details.pangkat')
            ->where('status', '=', 1)
            ->get();

        // jantina 1/2 = men/women, ketukangan_type 1/2 = officer/other rank.
        $keys = ['1-1' => 'manOfficer', '2-1' => 'womanOfficer', '1-2' => 'manOther', '2-2' => 'womanOther'];
        $totals = [];
        foreach ($users as $user) {
            $unit = (string) $user->unit_lama;
            $totals[$unit] ??= ['unit' => $unit, 'manOfficer' => 0, 'womanOfficer' => 0, 'manOther' => 0, 'womanOther' => 0];
            if ($key = $keys[$user->jantina . '-' . $user->ketukangan_type] ?? null) {
                $totals[$unit][$key]++;
            }
        }

        return response()->json(['rows' => array_values($totals)]);
    }

    // --- Excel exports ----------------------------------------------------

    public function excel(Request $request, string $type)
    {
        // Every export writes into storage/Order-Report before streaming it.
        File::ensureDirectoryExists(storage_path('Order-Report'));

        $id = (string) $request->query('id');
        $response = match ($type) {
            'uniform' => $this->orderReports->adminCreatExcelUniform($request, $id),
            'uniform-users' => $this->orderReports->adminCreatExcelUniformWithUserDetails($request, $id),
            'unit-users' => $this->orderReports->adminCreatExcelUniformWithUserDetailsUnit($request, $id),
            'cloth' => $this->orderReports->adminCreatExcelCloth($request, $id, (string) $request->query('slug')),
            'users-units' => $this->userReports->adminCreatExcelUnit($request),
            'users-strength' => $this->userReports->adminCreatExcelStrengthUnits($request),
            'users-uniform-tags' => $this->userReports->adminCreatExcelUniformTags($request),
            'users-without-orders' => $this->userReports->adminCreatExcelWithoutOrders($request),
            default => null,
        };

        if (!$response) {
            return response()->json(['message' => 'Unknown report.'], 404);
        }

        return $response->deleteFileAfterSend(true);
    }

    private function orderedUniformsByUser(array $userIds): array
    {
        $orders = $this->orderUniforms->expandByUniform(DB::table('orders')->where('deleted', '=', 0)->whereIn('user_id', $userIds)->get());
        $ordered = [];
        foreach ($orders as $order) {
            $ordered[$order->user_id][$order->uniforms_id] = true;
        }
        return $ordered;
    }

    private function findUniform(Request $request): ?object
    {
        return DB::table('uniforms')->where('id', '=', (int) $request->query('uniformId'))->first();
    }

    private function findUnit(Request $request): ?object
    {
        return DB::table('units')->where('id', '=', (int) $request->query('unitId'))->first();
    }

    private function uniformLabel(object $u): string
    {
        return $u->uniform_type . ($u->uniform_name ? ' (' . $u->uniform_name . ')' : '');
    }

    private function notFound(string $what)
    {
        return response()->json(['message' => $what . ' not found.'], 404);
    }
}
