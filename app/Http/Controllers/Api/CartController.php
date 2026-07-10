<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderCheckoutService;
use App\Services\UniformCartRules;
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

        DB::table('cart_items')->updateOrInsert(
            ['gen_user_id' => $genUser->id, 'uniforms_id' => $uniformsId, 'clothes_slug' => $clothesSlug],
            ['clothes_type' => $cloth->clothes_type, 'size' => json_encode($normalizedSize), 'updated_at' => now(), 'created_at' => now()]
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
            ];
        }

        app(OrderCheckoutService::class)->checkoutForUser($genUser->id, $cartByUniform);

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
            ->where('cart_items.gen_user_id', '=', $genUserId)
            ->select('cart_items.*', 'uniforms.uniform_type', 'uniforms.uniform_name')
            ->get();

        $items = $rows->map(function ($row) {
            $size = json_decode($row->size, true);
            return [
                'uniformsId' => (string) $row->uniforms_id,
                'uniformName' => $row->uniform_name ?: $row->uniform_type,
                'clothesSlug' => $row->clothes_slug,
                'clothesType' => $row->clothes_type,
                'size' => $size,
            ];
        })->values();

        return ['items' => $items, 'count' => $items->count()];
    }
}
