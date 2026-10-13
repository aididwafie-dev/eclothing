<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminBaseService;
use Illuminate\Http\Request;

/**
 * JSON counterpart to AdminBaseController + AdminController::deleteBase,
 * for the admin side of the Next.js UI. Superadmin-only -- gated by the
 * 'admin.api.scope' middleware, mirroring EnsureAdminRoleAllowsRoute's
 * web allowlist (Bases is not on it).
 */
class BaseController extends Controller
{
    public function __construct(private AdminBaseService $bases)
    {
    }

    public function index()
    {
        return response()->json([
            'bases' => $this->bases->listWithUnitCounts()->map(fn ($b) => [
                'id' => (string) $b->id,
                'base' => $b->base,
                'orgName' => $b->org_name,
                'active' => (int) $b->status === 1,
                'unitCount' => (int) $b->unit_count,
            ])->values(),
        ]);
    }

    public function show(string $id)
    {
        $base = $this->bases->find((int) $id);
        if (!$base) {
            return response()->json(['message' => 'Base not found.'], 404);
        }

        $units = $this->bases->allUnitsWithBase();

        return response()->json([
            'base' => [
                'id' => (string) $base->id,
                'base' => $base->base,
                'orgName' => $base->org_name,
                'active' => (int) $base->status === 1,
            ],
            'units' => $units->map(fn ($u) => [
                'id' => (string) $u->id,
                'value' => $u->value,
                'baseHq' => $u->base_hq !== null ? (string) $u->base_hq : null,
                'assignedHere' => (int) ($u->base_hq ?? 0) === (int) $base->id,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $result = $this->bases->create(
            (string) $request->input('base'),
            $request->input('orgName'),
            $request->boolean('active') ? 1 : 0
        );

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function update(Request $request, string $id)
    {
        $result = $this->bases->update(
            (int) $id,
            (string) $request->input('base'),
            $request->input('orgName'),
            $request->boolean('active') ? 1 : 0,
            (array) $request->input('unitIds', [])
        );

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }

        return response()->json(['message' => $result['message']]);
    }

    public function destroy(string $id)
    {
        $this->bases->delete((int) $id);
        return response()->json(['message' => 'The base was successfully deleted.']);
    }
}
