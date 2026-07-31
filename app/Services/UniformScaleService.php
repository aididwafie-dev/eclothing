<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the entitlement scale ("Skala Kelayakan Pakaian"): how many pieces
 * of a clothing item or accessory a rank may order.
 *
 * Three states, and the distinction matters:
 *
 *   no row      -> not configured. No cap, item orderable. This is the
 *                  pre-existing behaviour and the default, so introducing the
 *                  feature does not stop ordering before scales are entered.
 *   0           -> explicitly not entitled. Item is hidden from selection and
 *                  refused by both carts.
 *   n > 0       -> orderable, at most n pieces.
 *
 * Shared by DashboardController (session cart) and Api\CartController (DB
 * cart) so the web app and the mobile API enforce identical limits.
 */
class UniformScaleService
{
    /** Cache per request: pangkat_id => [uniform_clothes_id => max_quantity] */
    private array $cache = [];

    /** Cache per request: pangkat_id => [uniforms_id, ...] hidden from the cart */
    private array $hiddenCache = [];

    public function tableExists(): bool
    {
        try {
            return Schema::hasTable('uniform_scales');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function hiddenTableExists(): bool
    {
        try {
            return Schema::hasTable('uniform_rank_hidden');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Uniform ids hidden from the order cart for a rank.
     *
     * @return array<int> list of uniforms_id
     */
    public function hiddenUniformsForRank(?int $pangkatId): array
    {
        if (!$pangkatId || !$this->hiddenTableExists()) {
            return [];
        }

        if (isset($this->hiddenCache[$pangkatId])) {
            return $this->hiddenCache[$pangkatId];
        }

        try {
            $ids = DB::table('uniform_rank_hidden')
                ->where('pangkat_id', '=', $pangkatId)
                ->pluck('uniforms_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } catch (\Throwable $e) {
            $ids = [];
        }

        return $this->hiddenCache[$pangkatId] = $ids;
    }

    public function isUniformHidden(?int $pangkatId, int $uniformsId): bool
    {
        return in_array($uniformsId, $this->hiddenUniformsForRank($pangkatId), true);
    }

    /**
     * @return array<int, int> uniform_clothes_id => max_quantity (0 = blocked)
     */
    public function scalesForRank(?int $pangkatId): array
    {
        if (!$pangkatId || !$this->tableExists()) {
            return [];
        }

        if (isset($this->cache[$pangkatId])) {
            return $this->cache[$pangkatId];
        }

        try {
            $rows = DB::table('uniform_scales')
                ->where('pangkat_id', '=', $pangkatId)
                ->pluck('max_quantity', 'uniform_clothes_id')
                ->toArray();
        } catch (\Throwable $e) {
            $rows = [];
        }

        $scales = [];
        foreach ($rows as $clothesId => $max) {
            $scales[(int) $clothesId] = (int) $max;
        }

        return $this->cache[$pangkatId] = $scales;
    }

    /**
     * Maximum pieces of $clothesId that $pangkatId may order.
     * null means "not configured" -- allowed, with no cap.
     */
    public function maxFor(?int $pangkatId, int $clothesId): ?int
    {
        $scales = $this->scalesForRank($pangkatId);

        return array_key_exists($clothesId, $scales) ? $scales[$clothesId] : null;
    }

    public function isBlocked(?int $pangkatId, int $clothesId): bool
    {
        return $this->maxFor($pangkatId, $clothesId) === 0;
    }

    /**
     * Clamp a requested quantity to what the rank is entitled to.
     * Returns at least 1 for anything not blocked.
     */
    public function clampQuantity(?int $pangkatId, int $clothesId, $requested): int
    {
        $quantity = (int) $requested;
        if ($quantity < 1) {
            $quantity = 1;
        }

        $max = $this->maxFor($pangkatId, $clothesId);
        if ($max === null) {
            return $quantity;
        }

        if ($max === 0) {
            return 0;
        }

        return min($quantity, $max);
    }

    /**
     * Rank id for a gen_users id, read from personal_details.
     */
    public function rankForUser($userId): ?int
    {
        if (!$userId) {
            return null;
        }

        try {
            $pangkat = DB::table('personal_details')
                ->where('user_id', '=', $userId)
                ->value('pangkat');
        } catch (\Throwable $e) {
            return null;
        }

        return $pangkat !== null && $pangkat !== '' ? (int) $pangkat : null;
    }
}
