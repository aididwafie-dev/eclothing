<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ketukangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to AdminTredController + AdminController::deleteTred,
 * for the admin side of the Next.js UI. Superadmin-only.
 */
class TredController extends Controller
{
    public function index()
    {
        return response()->json([
            'treds' => DB::table('ketukangans')->get()->map(fn ($t) => $this->present($t))->values(),
        ]);
    }

    public function show(string $id)
    {
        $tred = DB::table('ketukangans')->where('id', '=', (int) $id)->first();
        if (!$tred) {
            return response()->json(['message' => 'Tred not found.'], 404);
        }

        return response()->json(['tred' => $this->present($tred)]);
    }

    public function store(Request $request)
    {
        $value = trim((string) $request->input('value'));
        if ($value === '') {
            return response()->json(['message' => 'Tred value was empty. Please try again'], 422);
        }

        $tred = new Ketukangan;
        $tred->value = $value;
        $tred->officer_recruit = (int) $request->input('officerRecruit');
        $tred->save();

        return response()->json(['message' => 'Tred added successfully']);
    }

    public function update(Request $request, string $id)
    {
        $tred = Ketukangan::find((int) $id);
        if (!$tred) {
            return response()->json(['message' => 'Tred not found.'], 404);
        }

        $value = trim((string) $request->input('value'));
        if ($value === '') {
            return response()->json(['message' => 'Tred value was empty. Please try again'], 422);
        }

        $tred->value = $value;
        $tred->officer_recruit = (int) $request->input('officerRecruit');
        $tred->save();

        return response()->json(['message' => 'Tred saved successfully']);
    }

    public function destroy(string $id)
    {
        DB::table('ketukangans')->where('id', '=', (int) $id)->delete();
        return response()->json(['message' => 'The tred was successfully deleted.']);
    }

    private function present(object $t): array
    {
        return [
            'id' => (string) $t->id,
            'value' => $t->value,
            'officerRecruit' => (int) $t->officer_recruit,
        ];
    }
}
