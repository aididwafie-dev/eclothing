<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to the All Users section of AdminController, for the
 * admin side of the Next.js UI. Superadmin-only.
 */
class UserController extends Controller
{
    use \App\Http\Controllers\Concerns\LoadsPersonalDetailDropdowns;

    private const PER_PAGE = 25;

    public function __construct(private AdminUserService $users)
    {
    }

    public function index(Request $request)
    {
        $sortColumns = ['s_id', 'pangkat', 'name', 'unit', 'status', 'created_at', 'updated_at'];
        $sortColumn = $request->query('sortColumn', 's_id');
        $sortColumn = in_array($sortColumn, $sortColumns, true) ? $sortColumn : 's_id';
        $sortDir = strtolower((string) $request->query('sortDir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $page = max(1, (int) $request->query('page', 1));
        $perPage = min(100, max(1, (int) $request->query('perPage', self::PER_PAGE)));

        $result = $this->users->listRows(
            $request->query('filter'),
            (string) $request->query('search', ''),
            $sortColumn,
            $sortDir,
            ($page - 1) * $perPage,
            $perPage
        );

        $rows = $result['rows']->map(fn ($row) => [
            'id' => (string) $row->id,
            'sId' => $row->s_id,
            'hasOrdered' => (bool) $row->has_ordered,
            'rankName' => $row->pangkat_name,
            'name' => $row->name,
            'unitName' => $row->unit ? ($row->unit_name ?? 'N/A') : null,
            'blocked' => (int) $row->status === 0,
            'activated' => (int) $row->activation_status === 1,
            'createdAt' => $row->created_at,
            'updatedAt' => $row->updated_at,
        ])->values();

        return response()->json([
            'users' => $rows,
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'page' => $page,
            'perPage' => $perPage,
            'filter' => $request->query('filter'),
        ]);
    }

    public function show(string $id)
    {
        $user = $this->users->find((int) $id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $personalDetail = DB::table('personal_details')->where('user_id', '=', $id)->first();

        return response()->json([
            'user' => ['id' => (string) $user->id, 'sId' => $user->s_id, 'email' => $user->email],
            'personalDetails' => $personalDetail ? [
                'sId' => $personalDetail->s_id,
                'name' => $personalDetail->name,
                'service' => $personalDetail->piliih_angkatan !== null ? (string) $personalDetail->piliih_angkatan : null,
                'ketukanganType' => (int) $personalDetail->ketukangan_type,
                'tred' => $personalDetail->ketukangan !== null ? (string) $personalDetail->ketukangan : null,
                'pangkat' => $personalDetail->pangkat !== null ? (string) $personalDetail->pangkat : null,
                'unit' => $personalDetail->unit !== null ? (string) $personalDetail->unit : null,
                'gender' => $personalDetail->jantina !== null ? (string) $personalDetail->jantina : null,
                'telephoneNumber' => $personalDetail->telephone_number,
                'dutyStatus' => $personalDetail->status_penggunaan !== null ? (string) $personalDetail->status_penggunaan : null,
                'religion' => $personalDetail->religion,
                'address' => array_pad(explode('|', (string) $personalDetail->address), 4, ''),
                'namaWaris' => $personalDetail->nama_waris,
                'telephoneNumberWaris' => $personalDetail->telephone_number_waris,
                'nameTag' => $personalDetail->name_tag,
                'unitLama' => $personalDetail->unit_lama,
                'kemLama' => $personalDetail->kem_lama,
                'splLama' => $personalDetail->spl_lama,
            ] : null,
            'position' => \Illuminate\Support\Facades\Schema::hasColumn('gen_users', 'position') ? trim((string) ($user->position ?? '')) : '',
            'dropdowns' => $this->dropdowns(),
        ]);
    }

    private function dropdowns(): array
    {
        $raw = $this->personalDetailsDropdownValues();
        $toOptions = fn ($rows) => collect($rows)->map(fn ($r) => ['id' => (string) $r->id, 'value' => $r->value])->values();

        return [
            'services' => $toOptions($raw['piliih_angkatans']),
            'tredOfficer' => $toOptions($raw['ketukangans_officer']),
            'tredRecruit' => $toOptions($raw['ketukangans_recruit']),
            'tredBoth' => $toOptions($raw['ketukangans_both']),
            'units' => $toOptions($raw['units']),
            'genders' => $toOptions($raw['jantinas']),
            'dutyStatuses' => $toOptions($raw['status_penggunaans']),
            'states' => array_map(fn ($s) => ['id' => $s, 'value' => $s], [
                'JOHOR', 'W.P. KUALA LUMPUR', 'W.P. LABUAN', 'W.P. PUTRAJAYA', 'KEDAH', 'KELANTAN',
                'MELAKA', 'NEGERI SEMBILAN', 'PAHANG', 'PERAK', 'PERLIS', 'PULAU PINANG', 'SABAH',
                'SARAWAK', 'SELANGOR', 'TERENGGANU', 'OTHERS',
            ]),
        ];
    }

    public function ranks(Request $request)
    {
        $pangkats = DB::table('pangkats')
            ->where('officer_recruit', '=', $request->query('tredType'))
            ->where('piliih_angkatan_id', '=', $request->query('service'))
            ->get();

        return response()->json([
            'ranks' => $pangkats->map(fn ($p) => ['id' => (string) $p->id, 'value' => $p->value])->values(),
        ]);
    }

    public function updateBasic(Request $request, string $id)
    {
        $password = $request->input('password');
        $result = $this->users->updateBasicDetails((int) $id, (string) $request->input('sId'), $password !== '' ? $password : null);

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }
        return response()->json(['message' => $result['message']]);
    }

    public function updatePersonal(Request $request, string $id)
    {
        $result = $this->users->updatePersonalDetails((int) $id, [
            'sId' => $request->input('s_id'),
            'name' => $request->input('name'),
            'service' => $request->input('piliih_angkatan'),
            'ketukanganType' => (int) $request->input('ketukangans_type'),
            'tredOfficer' => $request->input('ketukangans_officer'),
            'tredRecruit' => $request->input('ketukangans_recruit'),
            'pangkat' => $request->input('pangkat'),
            'unit' => $request->input('unit'),
            'gender' => $request->input('jantina'),
            'telephoneNumber' => $request->input('telephone_number'),
            'addressLine1' => $request->input('address1'),
            'addressLine2' => $request->input('address2'),
            'addressLine3' => $request->input('address3'),
            'addressLine4' => $request->input('address4'),
            'namaWaris' => $request->input('nama_waris'),
            'telephoneNumberWaris' => $request->input('tele_number_waris'),
            'dutyStatus' => $request->input('status_penggunaan'),
            'unitLama' => $request->input('unit_lama'),
            'nameTag' => $request->input('name_tag'),
            'religion' => $request->input('status_religion'),
            'religionOther' => $request->input('status_religion_others'),
            'kemLama' => $request->input('kem_lama'),
            'splLama' => $request->input('spl_lama'),
            'position' => $request->input('position'),
        ]);

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], 422);
        }
        return response()->json(['message' => $result['message']]);
    }

    public function toggleStatus(string $id)
    {
        $ok = $this->users->toggleAccessStatus((int) $id);
        if (!$ok) {
            return response()->json(['message' => 'User not found.'], 404);
        }
        return response()->json(['message' => 'Status updated.']);
    }

    public function blockAll()
    {
        $this->users->blockAll();
        return response()->json(['message' => 'All active users are now blocked.']);
    }

    public function unblockAll()
    {
        $this->users->unblockAll();
        return response()->json(['message' => 'All blocked users are now unblocked.']);
    }

    public function destroy(string $id)
    {
        $this->users->delete((int) $id);
        return response()->json(['message' => 'User deleted.']);
    }

    public function resendActivation(string $id)
    {
        $result = $this->users->resendActivationMail((int) $id);
        return response()->json(['message' => $result['message']], $result['ok'] ? 200 : 500);
    }
}
