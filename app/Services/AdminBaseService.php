<?php

namespace App\Services;

use App\Models\Base;
use Illuminate\Support\Facades\DB;

/**
 * Base CRUD + the unit-assignment checkbox grid on a base's edit screen,
 * extracted out of AdminBaseController/AdminController::deleteBase so the
 * Blade pages and the Api\Admin\BaseController JSON endpoints share one
 * copy of the rules.
 */
class AdminBaseService
{
    public function listWithUnitCounts()
    {
        return DB::table('bases')
            ->leftJoin('units', 'units.base_hq', '=', 'bases.id')
            ->select('bases.*', DB::raw('COUNT(units.id) as unit_count'))
            ->groupBy('bases.id', 'bases.base', 'bases.org_name', 'bases.status', 'bases.created_at', 'bases.updated_at')
            ->orderBy('bases.base')
            ->get();
    }

    public function find(int $id): ?object
    {
        return DB::table('bases')->where('id', '=', $id)->first();
    }

    public function allUnitsWithBase()
    {
        return DB::table('units')->orderBy('value')->get();
    }

    /** @return array{ok: bool, message: string} */
    public function create(string $baseName, ?string $orgName, $status): array
    {
        $baseName = trim($baseName);
        if ($baseName === '') {
            return ['ok' => false, 'message' => 'Base name was empty. Please try again'];
        }

        $base = new Base;
        $base->base = $baseName;
        $base->org_name = trim((string) $orgName) ?: null;
        $base->status = $status ?? 1;
        $base->save();

        return ['ok' => true, 'message' => 'Base added successfully'];
    }

    /**
     * @param array<int, int> $unitIds units to assign to this base; every
     *        other unit currently pointing here is detached.
     * @return array{ok: bool, message: string}
     */
    public function update(int $id, string $baseName, ?string $orgName, $status, array $unitIds): array
    {
        $baseName = trim($baseName);
        if ($baseName === '') {
            return ['ok' => false, 'message' => 'Base name was empty. Please try again'];
        }

        $base = Base::find($id);
        if (!$base) {
            return ['ok' => false, 'message' => 'Base not found.'];
        }

        $base->base = $baseName;
        $base->org_name = trim((string) $orgName) ?: null;
        $base->status = $status;
        $base->save();

        $this->syncUnits($id, $unitIds);

        return ['ok' => true, 'message' => 'Base saved successfully'];
    }

    public function delete(int $id): void
    {
        // units.base_hq is ON DELETE SET NULL, so its units simply lose
        // their base rather than being deleted.
        DB::table('bases')->where('id', '=', $id)->delete();
    }

    private function syncUnits(int $baseId, array $unitIds): void
    {
        $unitIds = array_filter(array_map('intval', $unitIds));

        DB::table('units')->where('base_hq', '=', $baseId)->update(['base_hq' => null]);
        if (!empty($unitIds)) {
            DB::table('units')->whereIn('id', $unitIds)->update(['base_hq' => $baseId]);
        }
    }
}
