<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The "Saiz Pakaian" tab's rules -- one size per member per clothing
 * item, separate from any order. Shared by the web Blade page
 * (DashboardController) and the mobile/Next.js JSON API
 * (Api\ProfileController) so both save/display under the exact same
 * rules instead of drifting.
 */
class ClothingSizeService
{
    /**
     * Every uniform and its clothing items, grouped one section per
     * uniform -- the catalog the order cart draws from.
     *
     * @return array<int, array{uniform: object, items: \Illuminate\Support\Collection}>
     */
    public function uniformGroups(): array
    {
        $uniforms = DB::table('uniforms')->orderBy('uniform_type')->get();
        $items = DB::table('uniform_clothes')->orderBy('accessory')->orderBy('clothes_type')->get();

        $itemsByUniform = [];
        foreach ($items as $item) {
            $itemsByUniform[(int) $item->uniforms_id][] = $item;
        }

        $grouped = [];
        foreach ($uniforms as $uniform) {
            $uniformItems = $itemsByUniform[(int) $uniform->id] ?? [];
            if (!$uniformItems) {
                continue;
            }
            $grouped[] = ['uniform' => $uniform, 'items' => collect($uniformItems)];
        }

        return $grouped;
    }

    /** @return array<int, string> uniform_clothes_id => saved size */
    public function savedSizesForUser(int $userId): array
    {
        if (!$userId || !Schema::hasTable('personal_clothing_sizes')) {
            return [];
        }

        return DB::table('personal_clothing_sizes')
            ->where('user_id', '=', $userId)
            ->pluck('size', 'uniform_clothes_id')
            ->all();
    }

    /**
     * One size per clothing item the member chose to set -- an item left
     * blank is simply not saved/updated, not cleared, so a partially
     * filled-in submission never wipes sizes set earlier.
     *
     * @param  array<int|string, mixed> $sizes uniform_clothes_id => size
     * @return int how many sizes were actually written
     */
    public function saveSizesForUser(int $userId, array $sizes): int
    {
        if (!Schema::hasTable('personal_clothing_sizes')) {
            return 0;
        }

        $validClothesIds = DB::table('uniform_clothes')->pluck('id')->all();
        $now = now();
        $saved = 0;

        foreach ($sizes as $clothesId => $size) {
            $clothesId = (int) $clothesId;
            $size = trim((string) $size);
            if ($size === '' || !in_array($clothesId, $validClothesIds, true)) {
                continue;
            }

            $existingId = DB::table('personal_clothing_sizes')
                ->where('user_id', '=', $userId)
                ->where('uniform_clothes_id', '=', $clothesId)
                ->value('id');

            if ($existingId) {
                DB::table('personal_clothing_sizes')->where('id', '=', $existingId)
                    ->update(['size' => mb_substr($size, 0, 255), 'updated_at' => $now]);
            } else {
                DB::table('personal_clothing_sizes')->insert([
                    'user_id' => $userId,
                    'uniform_clothes_id' => $clothesId,
                    'size' => mb_substr($size, 0, 255),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $saved++;
        }

        return $saved;
    }
}
