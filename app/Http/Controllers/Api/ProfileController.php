<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personal_detail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * JSON counterpart to DashboardController's personal-details flow
 * (index/personalDetailsDropdownValues/savePersonalDetails/ajaxLoadRankValues).
 *
 * Mirrors every field in personal_details.blade.php's form — see that file
 * for the canonical field names and required/optional semantics.
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $personalDetail = DB::table('personal_details')->where('user_id', '=', $genUser->id)->first();
        $hasPositionCol = Schema::hasTable('gen_users') && Schema::hasColumn('gen_users', 'position');

        return response()->json([
            'personalDetails' => $personalDetail ? $this->formatDetails($genUser->s_id, $personalDetail) : null,
            'position' => $hasPositionCol ? trim((string) ($genUser->position ?? '')) : '',
            'dropdowns' => $this->dropdowns(),
        ]);
    }

    public function update(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');

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
            'position' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string'],
            'address_city' => ['nullable', 'string'],
            'address_state' => ['nullable', 'string'],
            'address_postcode' => ['nullable', 'string'],
            'nama_waris' => ['nullable', 'string'],
            'telephone_number_waris' => ['nullable', 'string'],
            'name_tag' => ['nullable', 'string', 'max:8'],
            'unit_lama' => ['nullable', 'string'],
            'kem_lama' => ['nullable', 'string', 'max:255'],
            'spl_lama' => ['nullable', 'string'],
        ]);

        $existing = DB::table('personal_details')->where('user_id', '=', $genUser->id)->first();
        $personalDetail = $existing ? Personal_detail::find($existing->id) : new Personal_detail;

        $addressParts = [
            trim((string) ($validated['address_line1'] ?? '')),
            trim((string) ($validated['address_city'] ?? '')),
            trim((string) ($validated['address_state'] ?? '')),
            trim((string) ($validated['address_postcode'] ?? '')),
        ];
        $address = implode('|', $addressParts);

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
        $personalDetail->address = $address;
        $personalDetail->nama_waris = $validated['nama_waris'] ?? '';
        $personalDetail->telephone_number_waris = $validated['telephone_number_waris'] ?? '';
        $personalDetail->name_tag = $validated['name_tag'] ?? '';
        $personalDetail->unit_lama = $validated['unit_lama'] ?? '';
        $personalDetail->kem_lama = $validated['kem_lama'] ?? '';
        $personalDetail->spl_lama = $validated['spl_lama'] ?? '';
        $personalDetail->save();

        $genUsersUpdate = ['profile_status' => 1];
        $hasPositionCol = Schema::hasTable('gen_users') && Schema::hasColumn('gen_users', 'position');
        if ($hasPositionCol && array_key_exists('position', $validated)) {
            $position = trim((string) ($validated['position'] ?? ''));
            $genUsersUpdate['position'] = $position !== '' ? $position : null;
        }
        DB::table('gen_users')->where('id', '=', $genUser->id)->update($genUsersUpdate);

        $savedPosition = '';
        if ($hasPositionCol) {
            $refreshedUser = DB::table('gen_users')->where('id', '=', $genUser->id)->first();
            $savedPosition = $refreshedUser ? trim((string) ($refreshedUser->position ?? '')) : '';
        }

        return response()->json([
            'personalDetails' => $this->formatDetails($genUser->s_id, DB::table('personal_details')->where('id', '=', $personalDetail->id)->first()),
            'position' => $savedPosition,
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
        $addressParts = array_pad(explode('|', (string) ($row->address ?? ''), 4), 4, '');

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
            'address_line1' => $addressParts[0] ?? '',
            'address_city' => $addressParts[1] ?? '',
            'address_state' => $addressParts[2] ?? '',
            'address_postcode' => $addressParts[3] ?? '',
            'nama_waris' => $row->nama_waris ?? '',
            'telephone_number_waris' => $row->telephone_number_waris ?? '',
            'name_tag' => $row->name_tag ?? '',
            'unit_lama' => $row->unit_lama ?? '',
            'kem_lama' => $row->kem_lama ?? '',
            'spl_lama' => $row->spl_lama ?? '',
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
            'states' => [
                ['id' => 'JOHOR', 'value' => 'JOHOR'],
                ['id' => 'W.P. KUALA LUMPUR', 'value' => 'W.P. KUALA LUMPUR'],
                ['id' => 'W.P. LABUAN', 'value' => 'W.P. LABUAN'],
                ['id' => 'W.P. PUTRAJAYA', 'value' => 'W.P. PUTRAJAYA'],
                ['id' => 'KEDAH', 'value' => 'KEDAH'],
                ['id' => 'KELANTAN', 'value' => 'KELANTAN'],
                ['id' => 'MELAKA', 'value' => 'MELAKA'],
                ['id' => 'NEGERI SEMBILAN', 'value' => 'NEGERI SEMBILAN'],
                ['id' => 'PAHANG', 'value' => 'PAHANG'],
                ['id' => 'PERAK', 'value' => 'PERAK'],
                ['id' => 'PERLIS', 'value' => 'PERLIS'],
                ['id' => 'PULAU PINANG', 'value' => 'PULAU PINANG'],
                ['id' => 'SABAH', 'value' => 'SABAH'],
                ['id' => 'SARAWAK', 'value' => 'SARAWAK'],
                ['id' => 'SELANGOR', 'value' => 'SELANGOR'],
                ['id' => 'TERENGGANU', 'value' => 'TERENGGANU'],
                ['id' => 'OTHERS', 'value' => 'OTHERS'],
            ],
        ];
    }
}
