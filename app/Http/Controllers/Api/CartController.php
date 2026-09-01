<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\OrderNotEditableException;
use App\Http\Controllers\Controller;
use App\Services\OrderCheckoutService;
use App\Services\OrderStatusService;
use App\Services\UniformCartRules;
use App\Services\UniformScaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to DashboardController's
 * addUniformCartItem/removeUniformCartItem/checkoutUniformCart, backed
 * by the cart_items table instead of the web app's session cart (see
 * the cart_items migration for why). Same UniformCartRules and
 * OrderCheckoutService as the web app, so checkout behavior matches
 * exactly.
 */
class CartController extends Controller
{
    public function show(Request $request)
    {
        return response()->json($this->snapshot($request->attributes->get('gen_user')->id));
    }

    public function add(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $uniformsId = (int) $request->input('uniformsId');
        $clothesSlug = (string) $request->input('clothesSlug');
        $size = $request->input('size');

        if (!$uniformsId || !$clothesSlug) {
            return response()->json(['message' => 'Invalid request'], 422);
        }

        $uniform = DB::table('uniforms')->where('id', '=', $uniformsId)->first();
        $cloth = DB::table('uniform_clothes')
            ->where('uniforms_id', '=', $uniformsId)
            ->where('clothes_slug', '=', $clothesSlug)
            ->first();

        if (!$uniform || !$cloth) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $normalizedSize = UniformCartRules::normalizeSize($size);

        if (UniformCartRules::isEmptySize($normalizedSize)) {
            DB::table('cart_items')
                ->where('gen_user_id', '=', $genUser->id)
                ->where('uniforms_id', '=', $uniformsId)
                ->where('clothes_slug', '=', $clothesSlug)
                ->delete();

            return response()->json($this->snapshot($genUser->id));
        }

        // Same entitlement scale as the web cart, so the API cannot be used to
        // exceed a rank's allowance.
        $scaleService = app(UniformScaleService::class);
        $rankId = $scaleService->rankForUser($genUser->id);

        if ($scaleService->isBlocked($rankId, (int) $cloth->id)) {
            return response()->json(['message' => 'Item ini tidak layak untuk pangkat anda.'], 422);
        }

        $quantity = $scaleService->clampQuantity($rankId, (int) $cloth->id, $request->input('quantity', 1));

        DB::table('cart_items')->updateOrInsert(
            ['gen_user_id' => $genUser->id, 'uniforms_id' => $uniformsId, 'clothes_slug' => $clothesSlug],
            ['clothes_type' => $cloth->clothes_type, 'size' => json_encode($normalizedSize), 'quantity' => $quantity, 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json($this->snapshot($genUser->id));
    }

    public function remove(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

        DB::table('cart_items')
            ->where('gen_user_id', '=', $genUser->id)
            ->where('uniforms_id', '=', (int) $request->input('uniformsId'))
            ->where('clothes_slug', '=', (string) $request->input('clothesSlug'))
            ->delete();

        return response()->json($this->snapshot($genUser->id));
    }

    /**
     * Seeds the cart with an existing order's contents so the member can see
     * what they ordered - sizes and quantities - and adjust it.
     *
     * The mobile client cannot do this itself: `GET /api/orders` reports
     * `clothes`/`size` for display but not `clothes_slug`, which is what the
     * cart is keyed by. Doing it here also keeps the Pending-only rule and
     * the rank entitlement scale in one place.
     */
    public function loadFromOrder(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

        $order = DB::table('orders')
            ->where('id', '=', $request->input('orderId'))
            ->where('user_id', '=', $genUser->id)
            ->where('deleted', '=', 0)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $statusSvc = app(OrderStatusService::class);
        if (!$statusSvc->isOrderEditable($order->status ?? null)) {
            $label = $statusSvc->orderStatusMeta($order->status ?? null)['label'];

            return response()->json([
                'message' => 'This order is ' . $label . ' and can no longer be changed. Only orders that are still Pending can be edited.',
            ], 403);
        }

        $items = DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get();
        $scaleService = app(UniformScaleService::class);
        $rankId = $scaleService->rankForUser($genUser->id);

        DB::transaction(function () use ($genUser, $order, $items, $scaleService, $rankId) {
            // Replace this uniform's cart lines rather than merging: the cart
            // should show the order as it currently stands, not the order plus
            // whatever the member happened to leave behind earlier. Lines for
            // other uniforms are untouched.
            DB::table('cart_items')
                ->where('gen_user_id', '=', $genUser->id)
                ->where('uniforms_id', '=', $order->uniforms_id)
                ->delete();

            foreach ($items as $item) {
                $cloth = DB::table('uniform_clothes')
                    ->where('uniforms_id', '=', $order->uniforms_id)
                    ->where('clothes_slug', '=', $item->clothes_slug)
                    ->first();

                // The item is no longer offered for this uniform, or the
                // member's rank is no longer entitled to it.
                if (!$cloth || $scaleService->isBlocked($rankId, (int) $cloth->id)) {
                    continue;
                }

                $size = UniformCartRules::normalizeSize($this->decodeOrderedSize($cloth, $item->size));
                if (UniformCartRules::isEmptySize($size)) {
                    continue;
                }

                DB::table('cart_items')->insert([
                    'gen_user_id' => $genUser->id,
                    'uniforms_id' => $order->uniforms_id,
                    'clothes_slug' => $item->clothes_slug,
                    'clothes_type' => $cloth->clothes_type,
                    'size' => json_encode($size),
                    // Re-clamped rather than trusted: the member's rank may
                    // have changed since the order was placed.
                    'quantity' => $scaleService->clampQuantity($rankId, (int) $cloth->id, $item->quantity ?? 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json($this->snapshot($genUser->id));
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

    public function checkout(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

        $rows = DB::table('cart_items')->where('gen_user_id', '=', $genUser->id)->get();
        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 422);
        }

        $cartByUniform = [];
        foreach ($rows as $row) {
            $cartByUniform[$row->uniforms_id][$row->clothes_slug] = [
                'clothes_slug' => $row->clothes_slug,
                'size' => json_decode($row->size, true),
                'quantity' => isset($row->quantity) ? (int) $row->quantity : 1,
            ];
        }

        try {
            app(OrderCheckoutService::class)->checkoutForUser($genUser->id, $cartByUniform);
        } catch (OrderNotEditableException $e) {
            // The cart is deliberately left intact so the member can drop the
            // offending uniform and still check the rest out.
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $orderIds = DB::table('orders')
            ->where('user_id', '=', $genUser->id)
            ->whereIn('uniforms_id', array_keys($cartByUniform))
            ->where('deleted', '=', 0)
            ->pluck('id');

        DB::table('cart_items')->where('gen_user_id', '=', $genUser->id)->delete();

        return response()->json(['orderIds' => $orderIds]);
    }

    private function snapshot(int $genUserId): array
    {
        $rows = DB::table('cart_items')
            ->join('uniforms', 'uniforms.id', '=', 'cart_items.uniforms_id')
            ->leftJoin('uniform_clothes', function ($join) {
                $join->on('uniform_clothes.uniforms_id', '=', 'cart_items.uniforms_id')
                    ->on('uniform_clothes.clothes_slug', '=', 'cart_items.clothes_slug');
            })
            ->where('cart_items.gen_user_id', '=', $genUserId)
            ->select('cart_items.*', 'uniforms.uniform_type', 'uniforms.uniform_name', 'uniform_clothes.clothes_photo')
            ->get();

        $items = $rows->map(function ($row) {
            $size = json_decode($row->size, true);
            return [
                'uniformsId' => (string) $row->uniforms_id,
                'uniformName' => $row->uniform_name ?: $row->uniform_type,
                'clothesSlug' => $row->clothes_slug,
                'clothesType' => $row->clothes_type,
                'size' => $size,
                'quantity' => isset($row->quantity) ? (int) $row->quantity : 1,
                'imageUrl' => AssetController::urlFor($row->clothes_photo ?? null),
            ];
        })->values();

        return ['items' => $items, 'count' => $items->count()];
    }
}
