<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * JSON counterpart to AdminUnitController + AdminController::deleteUnit,
 * for the admin side of the Next.js UI. Superadmin-only.
 */
class UnitController extends Controller
{
    public function index()
    {
        $units = DB::table('units')
            ->leftJoin('bases', 'bases.id', '=', 'units.base_hq')
            ->select('units.*', 'bases.base as base_hq_name')
            ->get();

        return response()->json([
            'units' => $units->map(fn ($u) => $this->present($u) + ['baseHqName' => $u->base_hq_name])->values(),
        ]);
    }

    public function show(string $id)
    {
        $unit = DB::table('units')->where('id', '=', (int) $id)->first();
        if (!$unit) {
            return response()->json(['message' => 'Unit not found.'], 404);
        }

        return response()->json([
            'unit' => $this->present($unit),
            'bases' => $this->bases(),
        ]);
    }

    /** The BASE/HQ dropdown for the Add Unit form. */
    public function bases()
    {
        return DB::table('bases')->orderBy('base')->get()
            ->map(fn ($b) => ['id' => (string) $b->id, 'value' => $b->base])
            ->values();
    }

    public function store(Request $request)
    {
        $value = strtoupper(trim((string) $request->input('value')));
        if ($value === '') {
            return response()->json(['message' => 'Unit value was empty. Please try again'], 422);
        }

        $unit = new Unit;
        $unit->value = $value;
        $this->fillLocation($unit, $request);
        $unit->save();

        return response()->json(['message' => 'Unit added successfully']);
    }

    public function update(Request $request, string $id)
    {
        $unit = Unit::find((int) $id);
        if (!$unit) {
            return response()->json(['message' => 'Unit not found.'], 404);
        }

        $value = strtoupper(trim((string) $request->input('value')));
        if ($value === '') {
            return response()->json(['message' => 'Unit value was empty. Please try again'], 422);
        }

        $unit->value = $value;
        $this->fillLocation($unit, $request);
        $unit->save();

        return response()->json(['message' => 'Unit saved successfully']);
    }

    public function destroy(string $id)
    {
        DB::table('units')->where('id', '=', (int) $id)->delete();
        return response()->json(['message' => 'The unit was successfully deleted.']);
    }

    private function present(object $u): array
    {
        return [
            'id' => (string) $u->id,
            'value' => $u->value,
            'baseHq' => isset($u->base_hq) ? (string) $u->base_hq : null,
            'org' => $u->org ?? null,
        ];
    }

    /** Same rules as AdminUnitController::fillLocation. */
    private function fillLocation(Unit $unit, Request $request): void
    {
        if (!Schema::hasColumn('units', 'base_hq')) {
            return;
        }

        $baseId = (int) $request->input('baseHq');
        $unit->base_hq = $baseId > 0 ? $baseId : null;

        $org = mb_substr(strtoupper(trim((string) $request->input('org'))), 0, 255);
        $unit->org = $org !== '' ? $org : null;
    }
}
