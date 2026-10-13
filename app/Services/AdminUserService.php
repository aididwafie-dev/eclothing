<?php

namespace App\Services;

use App\Models\Gen_user;
use App\Models\Personal_detail;
use App\Support\PasswordHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The All Users list + the basic/personal-details edit actions,
 * extracted out of AdminController. The original list query
 * (ajaxDatatableUsersDetails) built raw SQL by string-concatenating the
 * search value and filter directly into it -- a real SQL-injection hole,
 * fixed here with parameterized Eloquent query building shared by both
 * the Blade DataTables feed and Api\Admin\UserController.
 */
class AdminUserService
{
    public const FILTERS = ['incomplete_rank', 'incomplete_name', 'incomplete_unit', 'incomplete_personal', 'incomplete_uniform', 'complete_all'];

    /**
     * One row per member, with everything the list needs to render:
     * whether they've ordered, rank/unit labels resolved, and
     * search/filter/sort/paging applied. Never trusts $search/$filter as
     * anything but bound values.
     */
    public function listRows(?string $filter, string $search, string $sortColumn, string $sortDir, int $start, int $length): array
    {
        $query = DB::table('gen_users')
            ->leftJoin('personal_details', 'personal_details.user_id', '=', 'gen_users.id')
            ->select(
                'gen_users.id',
                'gen_users.s_id',
                'gen_users.status',
                'gen_users.activation_status',
                'gen_users.profile_status',
                'gen_users.created_at',
                'gen_users.updated_at',
                'personal_details.name',
                'personal_details.unit',
                'personal_details.pangkat',
                DB::raw('EXISTS (SELECT 1 FROM orders WHERE orders.deleted = 0 AND orders.user_id = gen_users.id) as has_ordered')
            );

        $recordsTotal = DB::table('gen_users')->count();

        if ($filter && in_array($filter, self::FILTERS, true)) {
            match ($filter) {
                'incomplete_rank' => $query->where(fn ($q) => $q->whereNull('personal_details.pangkat')->orWhere('personal_details.pangkat', '=', '')),
                'incomplete_name' => $query->where(fn ($q) => $q->whereNull('personal_details.name')->orWhere('personal_details.name', '=', '')),
                'incomplete_unit' => $query->where(fn ($q) => $q->whereNull('personal_details.unit')->orWhere('personal_details.unit', '=', '')),
                'incomplete_personal' => $query->where(fn ($q) => $q->whereNull('gen_users.profile_status')->orWhere('gen_users.profile_status', '!=', 1)),
                'incomplete_uniform' => $query->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->where('orders.deleted', '=', 0)->whereColumn('orders.user_id', 'gen_users.id')),
                'complete_all' => $query
                    ->whereNotNull('personal_details.pangkat')->where('personal_details.pangkat', '!=', '')
                    ->whereNotNull('personal_details.name')->where('personal_details.name', '!=', '')
                    ->whereNotNull('personal_details.unit')->where('personal_details.unit', '!=', '')
                    ->where('gen_users.profile_status', '=', 1)
                    ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('orders')->where('orders.deleted', '=', 0)->whereColumn('orders.user_id', 'gen_users.id')),
                default => null,
            };
        }

        $search = trim($search);
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('personal_details.name', 'like', $like)
                    ->orWhere('personal_details.pangkat', 'like', $like)
                    ->orWhere('personal_details.unit', 'like', $like)
                    ->orWhere('gen_users.s_id', 'like', $like);
            });
        }

        $recordsFiltered = (clone $query)->count();

        $sortColumns = ['s_id' => 'gen_users.s_id', 'pangkat' => 'personal_details.pangkat', 'name' => 'personal_details.name',
            'unit' => 'personal_details.unit', 'status' => 'gen_users.status', 'created_at' => 'gen_users.created_at', 'updated_at' => 'gen_users.updated_at'];
        $query->orderBy($sortColumns[$sortColumn] ?? 'gen_users.id', $sortDir === 'desc' ? 'desc' : 'asc');

        $rows = $query->offset($start)->limit($length)->get();

        // Resolve rank/unit labels in bulk rather than per row.
        $pangkatIds = $rows->pluck('pangkat')->filter()->unique()->values();
        $unitIds = $rows->pluck('unit')->filter()->unique()->values();
        $pangkats = $pangkatIds->isEmpty() ? collect() : DB::table('pangkats')->whereIn('id', $pangkatIds)->pluck('value', 'id');
        $units = $unitIds->isEmpty() ? collect() : DB::table('units')->whereIn('id', $unitIds)->pluck('value', 'id');

        $rows = $rows->map(function ($row) use ($pangkats, $units) {
            $row->pangkat_name = $row->pangkat ? ($pangkats[$row->pangkat] ?? null) : null;
            $row->unit_name = $row->unit ? ($units[$row->unit] ?? null) : null;
            return $row;
        });

        return ['rows' => $rows, 'recordsTotal' => $recordsTotal, 'recordsFiltered' => $recordsFiltered];
    }

    public function find(int $id): ?object
    {
        return DB::table('gen_users')->where('id', '=', $id)->first();
    }

    /** @return array{ok: bool, message: string} */
    public function updateBasicDetails(int $id, string $sId, ?string $password): array
    {
        $genuser = Gen_user::find($id);
        if (!$genuser) {
            return ['ok' => false, 'message' => 'User not found.'];
        }

        if ($password !== null && trim($password) !== '') {
            $genuser->password = PasswordHasher::make($password);
        }
        $genuser->s_id = $sId;
        $genuser->save();

        $personalDetail = DB::table('personal_details')->where('user_id', '=', $id)->first();
        if ($personalDetail) {
            $detail = Personal_detail::find($personalDetail->id);
            $detail->s_id = $sId;
            $detail->save();
        }

        return ['ok' => true, 'message' => 'Basic details saved successfully.'];
    }

    /**
     * @param array{name: string, service: mixed, ketukanganType: int, tredOfficer: mixed, tredRecruit: mixed,
     *     pangkat: mixed, unit: mixed, gender: mixed, telephoneNumber: string, addressLine1: ?string,
     *     addressLine2: ?string, addressLine3: ?string, addressLine4: ?string, namaWaris: ?string,
     *     telephoneNumberWaris: ?string, dutyStatus: mixed, unitLama: ?string, nameTag: ?string,
     *     religion: ?string, religionOther: ?string, kemLama: ?string, splLama: ?string, position: ?string} $data
     * @return array{ok: bool, message: string}
     */
    public function updatePersonalDetails(int $userId, array $data): array
    {
        $detail = DB::table('personal_details')->where('user_id', '=', $userId)->first();
        if (!$detail) {
            return ['ok' => false, 'message' => 'This user has not filled in their personal details yet.'];
        }

        $personalDetail = Personal_detail::find($detail->id);

        $ketukanganType = (int) $data['ketukanganType'] === 1 ? 1 : 2;
        $ketukangan = $ketukanganType === 1 ? ($data['tredOfficer'] ?? null) : ($data['tredRecruit'] ?? null);

        $address = implode('|', [
            $data['addressLine1'] ?? '',
            $data['addressLine2'] ?? '',
            $data['addressLine3'] ?? '',
            $data['addressLine4'] ?? '',
        ]);

        $personalDetail->user_id = $userId;
        $personalDetail->s_id = $data['sId'] ?? $personalDetail->s_id;
        $personalDetail->name = $data['name'];
        $personalDetail->piliih_angkatan = $data['service'];
        $personalDetail->pangkat = $data['pangkat'];
        $personalDetail->ketukangan_type = $ketukanganType;
        $personalDetail->ketukangan = $ketukangan;
        $personalDetail->unit = $data['unit'];
        $personalDetail->jantina = $data['gender'];
        $personalDetail->telephone_number = $data['telephoneNumber'];
        $personalDetail->address = $address;
        $personalDetail->nama_waris = $data['namaWaris'] ?? '';
        $personalDetail->telephone_number_waris = $data['telephoneNumberWaris'] ?? '';
        $personalDetail->status_penggunaan = $data['dutyStatus'];
        $personalDetail->unit_lama = $data['unitLama'] ?? '';
        $personalDetail->name_tag = $data['nameTag'] ?? '';
        $personalDetail->religion = !empty($data['religion']) ? $data['religion'] : ($data['religionOther'] ?? null);
        $personalDetail->kem_lama = $data['kemLama'] ?? '';
        $personalDetail->spl_lama = $data['splLama'] ?? '';
        $personalDetail->save();

        if (Schema::hasTable('gen_users') && Schema::hasColumn('gen_users', 'position')) {
            $position = trim((string) ($data['position'] ?? ''));
            DB::table('gen_users')->where('id', '=', $userId)->update(['position' => $position !== '' ? $position : null]);
        }

        return ['ok' => true, 'message' => 'Personal Details successfully updated!'];
    }

    public function toggleAccessStatus(int $id): bool
    {
        $user = $this->find($id);
        if (!$user) {
            return false;
        }
        $newStatus = $user->status == 1 ? 0 : 1;
        DB::table('gen_users')->where('id', '=', $id)->update(['status' => $newStatus]);
        return true;
    }

    public function blockAll(): void
    {
        DB::table('gen_users')->where('status', '=', 1)->update(['status' => 0]);
    }

    public function unblockAll(): void
    {
        DB::table('gen_users')->where('status', '=', 0)->update(['status' => 1]);
    }

    public function delete(int $id): void
    {
        DB::table('gen_users')->where('id', '=', $id)->delete();
        DB::table('personal_details')->where('user_id', '=', $id)->delete();
    }

    /** @return array{ok: bool, message: string} */
    public function resendActivationMail(int $id): array
    {
        $user = $this->find($id);
        if (!$user || !$user->email) {
            return ['ok' => false, 'message' => 'Mail could not be sent'];
        }

        $activationUrl = secure_url('/verify-account/' . $user->auth_code);
        $body = 'Please Click On This link ' . $activationUrl . ' to activate your account.';

        try {
            \Mail::raw('Hi, welcome user! ' . $body, function ($message) use ($user) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($user->email)->subject('Activation Code For e-Clothing');
            });
            return ['ok' => true, 'message' => 'Mail is sent'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Mail could not be sent'];
        }
    }
}
