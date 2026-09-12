<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Turns an existing order back into cart lines, so a member can load a
 * Pending order into their cart, adjust it and check it out again.
 *
 * Shared by the mobile API (cart_items table) and the web app (session cart):
 * they store the lines differently but must agree on what the lines are,
 * including the rank entitlement scale and multi-select size decoding.
 */
class OrderCartSeeder
{
    public function __construct(private UniformScaleService $scale)
    {
    }

    /**
     * @return array<string, array{clothes_slug: string, clothes_type: string, size: array|string, quantity: int}>
     *         keyed by clothes_slug
     */
    public function linesForOrder(object $order, int $userId): array
    {
        $items = DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get();
        $rankId = $this->scale->rankForUser($userId);
        $lines = [];

        foreach ($items as $item) {
            $cloth = DB::table('uniform_clothes')
                ->where('uniforms_id', '=', $order->uniforms_id)
                ->where('clothes_slug', '=', $item->clothes_slug)
                ->first();

            // The item is no longer offered for this uniform, or the member's
            // rank is no longer entitled to it.
            if (!$cloth || $this->scale->isBlocked($rankId, (int) $cloth->id)) {
                continue;
            }

            $size = UniformCartRules::normalizeSize($this->decodeOrderedSize($cloth, $item->size));
            if (UniformCartRules::isEmptySize($size)) {
                continue;
            }

            $lines[$item->clothes_slug] = [
                'clothes_slug' => $item->clothes_slug,
                'clothes_type' => $cloth->clothes_type,
                'size' => $size,
                // Re-clamped rather than trusted: the member's rank may have
                // changed since the order was placed.
                'quantity' => $this->scale->clampQuantity($rankId, (int) $cloth->id, $item->quantity ?? 1),
            ];
        }

        return $lines;
    }

    /**
     * Inverse of OrderCheckoutService's size flattening: a multi-select
     * accessory is stored on the order as a comma-joined string, and has to
     * become an array again for the cart. Everything else round-trips as a
     * plain string.
     */
    private function decodeOrderedSize($cloth, $stored)
    {
        $stored = trim((string) $stored);

        if ($stored !== '' && $this->isMultiSelectCloth($cloth)) {
            return array_map('trim', explode(',', $stored));
        }

        return $stored;
    }

    /**
     * Mirrors the `multiselect` test in UniformController::clothes: only an
     * accessory whose clothes_size resolves to a *select* is offered as a
     * multi-select. An accessory with no size list is a plain toggle, and one
     * with a numeric size is free text -- neither is ever stored comma-joined,
     * so neither may be split back into an array.
     */
    private function isMultiSelectCloth($cloth): bool
    {
        if (strtolower((string) $cloth->clothes_type) !== 'accessories') {
            return false;
        }

        $clothesSize = (string) ($cloth->clothes_size ?? '');

        return $clothesSize !== ''
            && $clothesSize !== 'FIX'
            && !is_numeric(str_replace(['-', ' '], '', $clothesSize));
    }
}
