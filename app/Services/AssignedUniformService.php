<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Which uniforms a user may order depends on their trade (ketukangan)
 * and, for a handful of special-case ranks, their rank (pangkat) too.
 * Extracted from DashboardController::getUniformInfo (previously
 * duplicated near-identically as getAccessoriesInfo) so the mobile
 * API's UniformController resolves the same assignment.
 */
class AssignedUniformService
{
    private const SPECIAL_CASE_PANGKATS = [36, 38, 39, 45, 46, 54, 55];

    public function forPersonalDetail($personalDetail): array
    {
        if ($personalDetail->ketukangan_type == 1) {
            // Officers: assignment is by trade alone, regardless of rank.
            $assignment = DB::table('assigned_uniforms')
                ->where('ketukangans_id', '=', $personalDetail->ketukangan)
                ->first();
        } elseif (in_array($personalDetail->pangkat, self::SPECIAL_CASE_PANGKATS, true)) {
            $assignment = DB::table('assigned_uniforms')
                ->where('ketukangans_id', '=', $personalDetail->ketukangan)
                ->where('pangkats_id', '=', $personalDetail->pangkat)
                ->first();
        } else {
            $assignment = DB::table('assigned_uniforms')
                ->where('ketukangans_id', '=', $personalDetail->ketukangan)
                ->first();
        }

        if (!$assignment) {
            return [];
        }

        $uniformIds = json_decode($assignment->uniforms_id) ?? [];

        $uniforms = [];
        foreach ($uniformIds as $uniformId) {
            $uniform = DB::table('uniforms')->where('id', '=', $uniformId)->first();
            if ($uniform) {
                $uniforms[] = $uniform;
            }
        }

        return $uniforms;
    }
}
