<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personal_detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to DashboardController's personal-details flow
 * (index/getDropdownValues/savePersonalDetails/ajaxLoadRankValues).
 *
 * Deliberately narrower than the web form: next-of-kin
 * (nama_waris/telephone_number_waris), address lines 1-4, name_tag,
 * unit_lama, kem_lama and spl_lama aren't exposed here yet -- see
 * API_CONTRACT.md for the documented scope cut. `pangkat` (rank) IS
 * included despite not being in the original plas-mobile contract,
 * because AssignedUniformService requires it to resolve which
 * uniforms a user may order.
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $personalDetail = DB::table('personal_details')->where('user_id', '=', $genUser->id)->first();

        return response()->json([
            'personalDetails' => $personalDetail ? $this->formatDetails($genUser->s_id, $personalDetail) : null,
            'dropdowns' => $this->dropdowns(),
        ]);
    }

    public function update(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

        // service/tred/unit/gender/duty_status are all backed by NOT NULL
        // int columns in personal_details, so - like the web form's
        // dropdowns - they're effectively required, not optional.
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'service' => ['required'],
            'ketukangan_type' => ['required', 'in:1,2'],
            'tred' => ['required'],
            'pangkat' => ['required'],
            'unit' => ['required'],
            'gender' => ['required'],
            'telephone_number' => ['required', 'string'],
            'duty_status' => ['required'],
            'religion' => ['nullable', 'string'],
        ]);

        $existing = DB::table('personal_details')->where('user_id', '=', $genUser->id)->first();
        $personalDetail = $existing ? Personal_detail::find($existing->id) : new Personal_detail;

        if (!$existing) {
            // Columns this API doesn't expose yet (next-of-kin, address
            // lines, name_tag, unit_lama, kem_lama) are all NOT NULL with
            // no default. Only fill them in on a brand-new row - on an
            // update, leaving them untouched preserves whatever the web
            // form (or a previous mobile save) already put there instead
            // of silently blanking real data.
            $personalDetail->address = '';
            $personalDetail->nama_waris = '';
            $personalDetail->telephone_number_waris = '';
            $personalDetail->name_tag = '';
            $personalDetail->unit_lama = '';
            $personalDetail->kem_lama = '';
        }

        $personalDetail->user_id = $genUser->id;
        $personalDetail->s_id = $genUser->s_id;
        $personalDetail->name = $validated['name'];
        $personalDetail->piliih_angkatan = $validated['service'];
        $personalDetail->pangkat = $validated['pangkat'];
        $personalDetail->ketukangan_type = (int) $validated['ketukangan_type'];
        $personalDetail->ketukangan = $validated['tred'];
        $personalDetail->unit = $validated['unit'];
        $personalDetail->jantina = $validated['gender'];
        $personalDetail->telephone_number = $validated['telephone_number'];
        $personalDetail->status_penggunaan = $validated['duty_status'];
        $personalDetail->religion = $validated['religion'] ?? null;
        $personalDetail->save();

        DB::table('gen_users')->where('id', '=', $genUser->id)->update(['profile_status' => 1]);

        return response()->json([
            'personalDetails' => $this->formatDetails($genUser->s_id, DB::table('personal_details')->where('id', '=', $personalDetail->id)->first()),
        ]);
    }

    public function ranks(Request $request)
    {
        $serviceId = $request->query('piliihAngkatan');
        $tredType = $request->query('ketukanganType');

        $pangkats = DB::table('pangkats')
            ->where('officer_recruit', '=', $tredType)
            ->where('piliih_angkatan_id', '=', $serviceId)
            ->orderBy('pangkats_order', 'asc')
            ->get();

        return response()->json([
            'ranks' => $pangkats->map(fn ($p) => ['id' => (string) $p->id, 'value' => $p->value])->values(),
        ]);
    }

    private function formatDetails(string $sId, $row): array
    {
        return [
            's_id' => $sId,
            'name' => $row->name,
            'service' => $row->piliih_angkatan !== null ? (string) $row->piliih_angkatan : null,
            'ketukangan_type' => (int) $row->ketukangan_type,
            'tred' => $row->ketukangan !== null ? (string) $row->ketukangan : null,
            'pangkat' => $row->pangkat !== null ? (string) $row->pangkat : null,
            'unit' => $row->unit !== null ? (string) $row->unit : null,
            'gender' => $row->jantina !== null ? (string) $row->jantina : null,
            'telephone_number' => $row->telephone_number,
            'duty_status' => $row->status_penggunaan !== null ? (string) $row->status_penggunaan : null,
            'religion' => $row->religion,
        ];
    }

    private function dropdowns(): array
    {
        $toOptions = fn ($rows) => $rows->map(fn ($r) => ['id' => (string) $r->id, 'value' => $r->value])->values();

        return [
            'services' => $toOptions(DB::table('piliih_angkatans')->get()),
            'tredOfficer' => $toOptions(DB::table('ketukangans')->where('officer_recruit', '=', 1)->get()),
            'tredRecruit' => $toOptions(DB::table('ketukangans')->where('officer_recruit', '=', 2)->get()),
            'tredBoth' => $toOptions(DB::table('ketukangans')->where('officer_recruit', '=', 3)->get()),
            'units' => $toOptions(DB::table('units')->get()),
            'genders' => $toOptions(DB::table('jantinas')->get()),
            'dutyStatuses' => $toOptions(DB::table('status_penggunaans')->get()),
        ];
    }
}
