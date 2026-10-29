<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Uniform_clothe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to AdminUniformController + AdminAccessoriesController
 * (the Uniforms menu: uniform list, enable/disable, and each uniform's
 * clothes and accessories), for the admin side of the Next.js UI.
 * Creating/renaming a uniform itself reuses SystemSettingsController.
 * Superadmin-only.
 */
class UniformCatalogController extends Controller
{
    private const RELIGIONS = ['ISLAM', 'BUDDHISM', 'HINDU', 'CHRISTIANITY'];

    public function index()
    {
        return response()->json([
            'uniforms' => DB::table('uniforms')->orderBy('uniform_type')->get()
                ->map(fn ($u) => $this->presentUniform($u))->values(),
        ]);
    }

    public function show(string $id)
    {
        $uniform = DB::table('uniforms')->where('id', '=', (int) $id)->first();
        if (!$uniform) {
            return response()->json(['message' => 'Uniform not found.'], 404);
        }

        return response()->json(['uniform' => $this->presentUniform($uniform)]);
    }

    /** AdminController::changeUniformEnableDisable. */
    public function toggleActive(string $id)
    {
        $uniform = DB::table('uniforms')->where('id', '=', (int) $id)->first();
        if (!$uniform) {
            return response()->json(['message' => 'Uniform not found.'], 404);
        }

        $active = (int) $uniform->active !== 1;
        DB::table('uniforms')->where('id', '=', $uniform->id)->update([
            'active' => $active ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return response()->json(['active' => $active]);
    }

    /** clothesList.blade.php (kind=clothes) / accessoriesList.blade.php (kind=accessories). */
    public function items(Request $request, string $id)
    {
        $uniform = DB::table('uniforms')->where('id', '=', (int) $id)->first();
        if (!$uniform) {
            return response()->json(['message' => 'Uniform not found.'], 404);
        }

        $accessory = $request->query('kind') === 'accessories' ? 1 : 0;
        $items = DB::table('uniform_clothes')
            ->where('uniforms_id', '=', $uniform->id)
            ->where('accessory', '=', $accessory)
            ->get();

        $names = [
            'jantina' => DB::table('jantinas')->pluck('value', 'id'),
            'pangkat' => DB::table('pangkats')->pluck('value', 'id'),
            'ketukangan' => DB::table('ketukangans')->pluck('value', 'id'),
        ];
        $label = function (?string $ids, $map) {
            $values = collect($this->splitIds($ids))->map(fn ($i) => $map[$i] ?? null)->filter()->values();
            return $values->isEmpty() ? null : $values->implode(', ');
        };

        return response()->json([
            'uniform' => $this->presentUniform($uniform),
            'items' => $items->map(fn ($i) => [
                'id' => (string) $i->id,
                'clothesType' => $i->clothes_type,
                'clothesSize' => $i->clothes_size,
                'gender' => $label($i->jantina, $names['jantina']),
                'rank' => $label($i->pangkat, $names['pangkat']),
                'tred' => $label($i->ketukangan, $names['ketukangan']),
                'religion' => $i->religion ?: null,
            ])->values(),
        ]);
    }

    /** The gender/rank/tred filter choices on the add/edit cloth and accessory forms. */
    public function options()
    {
        return response()->json($this->filterOptions());
    }

    public function showItem(string $id)
    {
        $item = DB::table('uniform_clothes')->where('id', '=', (int) $id)->first();
        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }
        $uniform = DB::table('uniforms')->where('id', '=', $item->uniforms_id)->first();

        return response()->json([
            'item' => [
                'id' => (string) $item->id,
                'uniformId' => (string) $item->uniforms_id,
                'accessory' => (int) $item->accessory === 1,
                'clothesType' => $item->clothes_type,
                'clothesSize' => $item->clothes_size,
                'jantina' => $this->splitIds($item->jantina),
                'pangkat' => $this->splitIds($item->pangkat),
                'ketukangan' => $this->splitIds($item->ketukangan),
                'religion' => $this->split($item->religion),
            ],
            'uniform' => $uniform ? $this->presentUniform($uniform) : null,
        ] + $this->filterOptions());
    }

    /** saveAddedCloth / saveAddedAccessory. */
    public function storeItem(Request $request)
    {
        $uniformId = (int) $request->input('uniformId');
        if (!DB::table('uniforms')->where('id', '=', $uniformId)->exists()) {
            return response()->json(['message' => 'Uniform not found.'], 404);
        }

        $clothesType = trim((string) $request->input('clothesType'));
        if ($clothesType === '') {
            return response()->json(['message' => 'The name is required.'], 422);
        }

        $accessory = (bool) $request->input('accessory');

        // Same slug the Blade forms built: "<uniform id>_<words of the name>".
        $slug = strtolower((string) $uniformId);
        foreach (explode(' ', strtolower($clothesType)) as $word) {
            $slug .= '_' . $word;
        }

        $item = new Uniform_clothe;
        $item->uniforms_id = $uniformId;
        $item->clothes_slug = $slug;
        $item->accessory = $accessory ? 1 : 0;
        $this->fillItem($item, $request, $clothesType);
        $item->save();

        return response()->json(['message' => $accessory ? 'Accessory added.' : 'Cloth added.']);
    }

    /** saveEditedClothes / AdminAccessoriesController::saveEdited. */
    public function updateItem(Request $request, string $id)
    {
        $item = Uniform_clothe::find((int) $id);
        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        $clothesType = trim((string) $request->input('clothesType'));
        if ($clothesType === '') {
            return response()->json(['message' => 'The name is required.'], 422);
        }

        // The slug is left alone on edit: ordered_clothes rows reference it,
        // so changing it would detach every existing order of this item.
        $this->fillItem($item, $request, $clothesType);
        $item->save();

        return response()->json(['message' => 'Saved.']);
    }

    /** deleteCloth / AdminAccessoriesController::delete. */
    public function destroyItem(string $id)
    {
        $item = DB::table('uniform_clothes')->where('id', '=', (int) $id)->first();
        if (!$item) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        DB::table('uniform_clothes')->where('id', '=', $item->id)->delete();
        // deleteCloth also dropped the ordered rows for a cloth; deleting an
        // accessory never did.
        if ((int) $item->accessory !== 1) {
            DB::table('ordered_clothes')->where('clothes_slug', '=', $item->clothes_slug)->delete();
        }

        return response()->json(['message' => 'Removed.']);
    }

    private function fillItem(Uniform_clothe $item, Request $request, string $clothesType): void
    {
        $item->clothes_type = $clothesType;
        $size = trim((string) $request->input('clothesSize'));
        $item->clothes_size = $size !== '' ? strtoupper($size) : null;
        $item->jantina = $this->join($request->input('jantina'));
        $item->pangkat = $this->join($request->input('pangkat'));
        $item->ketukangan = $this->join($request->input('ketukangan'));
        $religions = array_values(array_intersect(self::RELIGIONS, (array) $request->input('religion', [])));
        $item->religion = $religions ? implode(',', $religions) : null;
    }

    private function filterOptions(): array
    {
        $recruit = [1 => 'OFFICER', 2 => 'RECRUIT'];

        return [
            'jantinas' => DB::table('jantinas')->get()
                ->map(fn ($j) => ['id' => (string) $j->id, 'value' => $j->value])->values(),
            'pangkats' => DB::table('pangkats')->orderBy('value')->get()
                ->map(fn ($p) => ['id' => (string) $p->id, 'value' => $p->value])->values(),
            'ketukangans' => DB::table('ketukangans')->orderBy('officer_recruit')->orderBy('value')->get()
                ->map(fn ($k) => [
                    'id' => (string) $k->id,
                    'value' => $k->value . ' - ' . ($recruit[(int) $k->officer_recruit] ?? 'BOTH'),
                ])->values(),
            'religions' => self::RELIGIONS,
        ];
    }

    private function presentUniform(object $u): array
    {
        $photo = $u->uniform_photo
            ? (str_contains($u->uniform_photo, '/') ? $u->uniform_photo : 'uploads/' . $u->uniform_photo)
            : 'front_end/images/uniforms/' . $u->uniform_type . '.jpg';

        return [
            'id' => (string) $u->id,
            'uniformType' => $u->uniform_type,
            'uniformName' => $u->uniform_name,
            'active' => (int) ($u->active ?? 0) === 1,
            'photoPath' => '/' . ltrim($photo, '/'),
        ];
    }

    private function join($ids): ?string
    {
        $ids = array_values(array_filter(array_map('intval', (array) $ids), fn ($i) => $i > 0));
        return $ids ? implode(',', $ids) : null;
    }

    private function splitIds(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($v) => $v !== '' && $v !== '0'));
    }

    private function split(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn ($v) => $v !== ''));
    }
}
