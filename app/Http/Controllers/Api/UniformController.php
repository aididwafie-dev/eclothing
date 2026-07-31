<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AssignedUniformService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to DashboardController::userUniformSelection and
 * loadUniformData -- which uniforms/clothes a user may order, using
 * the same assignment + jantina/pangkat/ketukangan/religion filtering
 * and ordered-size prefill rules. Cart annotation is sourced from the
 * mobile API's own cart_items table rather than the web app's session
 * cart (see CartController).
 */
class UniformController extends Controller
{
    public function index(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $personalDetail = DB::table('personal_details')->where('user_id', '=', $genUser->id)->first();

        if (!$personalDetail || $genUser->profile_status != 1) {
            return response()->json(['message' => 'Please complete your personal details first, before ordering uniform.'], 422);
        }

        $uniforms = app(AssignedUniformService::class)->forPersonalDetail($personalDetail);

        return response()->json([
            'uniforms' => array_map(fn ($u) => [
                'id' => (string) $u->id,
                'uniformType' => $u->uniform_type,
                'uniformName' => $u->uniform_name,
                'active' => (bool) $u->active,
            ], $uniforms),
        ]);
    }

    public function clothes(Request $request, string $uniformId)
    {
        $genUser = $request->attributes->get('gen_user');
        $personalDetail = DB::table('personal_details')->where('user_id', '=', $genUser->id)->first();

        if (!$personalDetail || $genUser->profile_status != 1) {
            return response()->json(['message' => 'Please complete your personal details first, before ordering uniform.'], 422);
        }

        $uniformClothes = DB::table('uniform_clothes')
            ->where('uniforms_id', '=', $uniformId)
            ->orderBy('accessory', 'asc')
            ->get()
            ->filter(function ($cloth) use ($personalDetail) {
                if ($cloth->jantina && $cloth->jantina != $personalDetail->jantina) {
                    return false;
                }
                if ($cloth->pangkat && $cloth->pangkat != $personalDetail->pangkat) {
                    return false;
                }
                if ($cloth->ketukangan && $cloth->ketukangan != $personalDetail->ketukangan) {
                    return false;
                }
                if ($cloth->religion && $cloth->religion != $personalDetail->religion) {
                    return false;
                }
                return true;
            })
            ->values();

        $orderedSizes = DB::table('ordered_clothes')
            ->leftJoin('orders', 'orders.id', '=', 'ordered_clothes.order_id')
            ->where('orders.uniforms_id', '=', $uniformId)
            ->where('orders.deleted', '=', 0)
            ->where('orders.user_id', '=', $genUser->id)
            ->orderBy('orders.created_at', 'desc')
            ->get()
            ->keyBy('clothes_slug');

        $cartRows = DB::table('cart_items')
            ->where('gen_user_id', '=', $genUser->id)
            ->where('uniforms_id', '=', $uniformId)
            ->get()
            ->keyBy('clothes_slug');

        $result = $uniformClothes->map(function ($cloth) use ($orderedSizes, $cartRows) {
            $cartRow = $cartRows->get($cloth->clothes_slug);
            $orderedSize = $orderedSizes->get($cloth->clothes_slug)?->size;

            $isMultiple = strtolower($cloth->clothes_type) === 'accessories';
            $shape = $this->sizeShape($cloth->clothes_size);

            return [
                'clothesSlug' => $cloth->clothes_slug,
                'clothesType' => $cloth->clothes_type,
                'sizeKind' => $shape['kind'] === 'select' && $isMultiple ? 'multiselect' : $shape['kind'],
                'sizeOptions' => $shape['options'],
                'inCart' => (bool) $cartRow,
                'cartValue' => $cartRow ? $this->decodeSize($cartRow->size) : $orderedSize,
                'imageUrl' => AssetController::urlFor($cloth->clothes_photo ?? null),
            ];
        });

        return response()->json(['clothes' => $result->values()]);
    }

    /**
     * Mirrors uniform_selection_form.blade.php's branching exactly:
     * empty -> toggle, 'FIX' -> fixed, else if the string is numeric
     * once dashes/spaces are stripped (this is true for BOTH a plain
     * number like "40" and a numeric range like "38-44", since
     * "38-44" -> "3844" is itself numeric) -> free-text ("text"), a
     * dash- or comma-delimited *non-numeric* range (e.g. "S-XXL")
     * resolved against the `sizes` lookup table -> "select", anything
     * else -> pipe-delimited explicit options -> "select".
     */
    private function sizeShape(?string $clothesSize): array
    {
        if ($clothesSize === null || $clothesSize === '') {
            return ['kind' => 'toggle', 'options' => []];
        }
        if ($clothesSize === 'FIX') {
            return ['kind' => 'fixed', 'options' => []];
        }

        $sizeCheck = str_replace(['-', ' '], '', $clothesSize);
        if (is_numeric($sizeCheck)) {
            return ['kind' => 'text', 'options' => []];
        }

        $sizeRange = explode('-', str_replace(' ', '', $clothesSize));
        if (!isset($sizeRange[1])) {
            $sizeRange = explode(',', str_replace(' ', '', $clothesSize));
        }

        if (isset($sizeRange[1])) {
            $sizes = DB::table('sizes')->get()->pluck('value')->toArray();
            $start = array_search($sizeRange[0], $sizes, true);
            $end = array_search($sizeRange[1], $sizes, true);
            if ($start === false || $end === false) {
                return ['kind' => 'select', 'options' => []];
            }
            return ['kind' => 'select', 'options' => array_values(array_slice($sizes, $start, $end - $start + 1))];
        }

        return ['kind' => 'select', 'options' => array_map('trim', explode('|', $clothesSize))];
    }

    private function decodeSize(?string $stored): mixed
    {
        if ($stored === null) {
            return null;
        }
        return json_decode($stored, true);
    }
}
