<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\AdminRoleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * JSON counterpart to AdminNewListController (Add New Admin / All Admins),
 * for the admin side of the Next.js UI. Superadmin-only.
 */
class AdminAccountController extends Controller
{
    public function __construct(private AdminRoleService $roles)
    {
    }

    public function index()
    {
        $rankMap = DB::table('pangkats')->pluck('value', 'id');

        $admins = DB::table('admins')->select($this->identityColumns())->orderBy('id')->get();

        return response()->json([
            'admins' => $admins->map(function ($a) use ($rankMap) {
                $parts = array_filter([
                    trim((string) ($rankMap[(int) ($a->pangkat_id ?? 0)] ?? '')),
                    trim((string) ($a->jawatan ?? '')),
                ], fn ($p) => $p !== '');
                $serviceId = trim((string) ($a->s_id ?? ''));

                return [
                    'id' => (string) $a->id,
                    'name' => $a->name,
                    'email' => $a->email,
                    'serviceId' => $serviceId !== '' ? $serviceId : (string) $a->username,
                    'jawatanRank' => implode(' / ', $parts),
                    'role' => $this->roles->normalize($a->role ?? null),
                    'active' => (int) $a->status === 1,
                ];
            })->values(),
        ]);
    }

    /** Step 1 of Add New Admin (getNewAdminDetails): who the service ID belongs to. */
    public function lookup(Request $request)
    {
        $sId = trim((string) $request->query('sId'));
        $candidate = $this->candidate($sId);
        if (is_string($candidate)) {
            return response()->json(['message' => $candidate], 422);
        }

        [$genUser, $personal] = $candidate;

        return response()->json([
            'candidate' => [
                'name' => $personal->name ?? '',
                'email' => $genUser->email,
                'username' => $genUser->s_id,
                'pangkatId' => isset($personal->pangkat) ? (string) (int) $personal->pangkat : '',
            ],
        ] + $this->options());
    }

    /** Step 2 of Add New Admin (addNewAdmin). */
    public function store(Request $request)
    {
        $candidate = $this->candidate(trim((string) $request->input('username')));
        if (is_string($candidate)) {
            return response()->json(['message' => $candidate], 422);
        }

        [$genUser, $personal] = $candidate;
        $name = $personal->name ?? '';

        $body = ' '.$name.'! You are added as a admin for the site e-Clothing. You can login as a admin to the site with the same username and password you use to login as a user. Thank you.';
        try {
            Mail::raw('Hi, '.$body, function ($message) use ($genUser) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($genUser->email)->subject('New Admin for DCS');
            });
        } catch (\Throwable $e) {
        }

        // The password is copied from the member account server-side, the
        // same value viewDetails_before_add.blade.php round-tripped through
        // a hidden field -- never sent to the browser here.
        $admin = new Admin;
        $admin->name = $name;
        $admin->email = $genUser->email;
        $admin->username = $genUser->s_id;
        $admin->password = $genUser->password;
        $admin->status = 1;
        $admin->save();

        $this->saveIdentity((int) $admin->id, $request, false);

        return response()->json(['message' => $name.' have been successfully added as admin.']);
    }

    public function show(string $id)
    {
        $admin = DB::table('admins')->where('id', '=', (int) $id)->select($this->identityColumns())->first();
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        return response()->json([
            'admin' => [
                'id' => (string) $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'sId' => $admin->s_id ?? '',
                'pangkatId' => !empty($admin->pangkat_id) ? (string) $admin->pangkat_id : '',
                'jawatan' => $admin->jawatan ?? '',
                'role' => $this->roles->normalize($admin->role ?? null),
                'baseId' => !empty($admin->base_id) ? (string) $admin->base_id : '',
            ],
        ] + $this->options());
    }

    public function update(Request $request, string $id)
    {
        if (!DB::table('admins')->where('id', '=', (int) $id)->exists()) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $this->saveIdentity((int) $id, $request, true);

        return response()->json(['message' => 'Admin details updated.']);
    }

    public function toggleStatus(Request $request, string $id)
    {
        if ((int) $id === (int) $request->attributes->get('admin')->id) {
            return response()->json(['message' => 'You can not change your status'], 422);
        }

        $admin = DB::table('admins')->where('id', '=', (int) $id)->first();
        if (!$admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $active = (int) $admin->status !== 1;
        DB::table('admins')->where('id', '=', $admin->id)->update([
            'status' => $active ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return response()->json(['active' => $active]);
    }

    public function destroy(Request $request, string $id)
    {
        if ((int) $id === (int) $request->attributes->get('admin')->id) {
            return response()->json(['message' => 'You can not delete your self'], 422);
        }

        DB::table('admins')->where('id', '=', (int) $id)->delete();

        return response()->json(['message' => 'Admin removed.']);
    }

    /**
     * The checks getNewAdminDetails makes before offering a service ID as a
     * new admin -- an error message string, or [gen_user, personal_details].
     */
    private function candidate(string $sId)
    {
        if ($sId === '') {
            return 'Put your valid service ID who is already registered';
        }
        if (DB::table('admins')->where('username', '=', $sId)->exists()) {
            return 'This user is already a Admin.';
        }

        $genUser = DB::table('gen_users')->where('s_id', '=', $sId)->first();
        if (!$genUser) {
            return 'Service ID does not exist';
        }
        if ((int) $genUser->profile_status === 0) {
            return 'This user have not filed his personal details.';
        }

        return [$genUser, DB::table('personal_details')->where('s_id', '=', $sId)->first()];
    }

    /** The rank / base / access dropdowns on both the add and edit forms. */
    private function options(): array
    {
        $roles = [];
        foreach ($this->roles->assignableRoles() as $key => $label) {
            $roles[] = ['id' => $key, 'value' => $label];
        }

        return [
            'pangkats' => DB::table('pangkats')->orderBy('pangkats_order')->orderBy('value')->get()
                ->map(function ($r) {
                    $ofc = (int) ($r->officer_recruit ?? 0);
                    $suffix = $ofc === 1 ? ' [Officer]' : ($ofc === 2 ? ' [Other Rank]' : '');
                    return ['id' => (string) $r->id, 'value' => trim((string) $r->value).$suffix];
                })->values(),
            'bases' => DB::table('bases')->orderBy('base')->get()
                ->map(fn ($b) => ['id' => (string) $b->id, 'value' => $b->base])->values(),
            'roles' => $roles,
        ];
    }

    /** The optional identity columns addNewAdmin/changeAdminDetails write. */
    private function saveIdentity(int $adminId, Request $request, bool $touch): void
    {
        $update = [];
        if (Schema::hasColumn('admins', 's_id')) {
            $sId = trim((string) $request->input('sId'));
            $update['s_id'] = $sId !== '' ? $sId : null;
        }
        if (Schema::hasColumn('admins', 'pangkat_id')) {
            $pangkatId = (int) $request->input('pangkatId');
            $update['pangkat_id'] = $pangkatId > 0 ? $pangkatId : null;
        }
        if (Schema::hasColumn('admins', 'jawatan')) {
            $jawatan = trim((string) $request->input('jawatan'));
            $update['jawatan'] = $jawatan !== '' ? $jawatan : null;
        }
        if (Schema::hasColumn('admins', 'role')) {
            // Normalized, so an unexpected value can never widen access.
            $update['role'] = $this->roles->normalize($request->input('role'));
        }
        if (Schema::hasColumn('admins', 'base_id')) {
            $baseId = (int) $request->input('baseId');
            $update['base_id'] = $baseId > 0 ? $baseId : null;
        }

        if (!empty($update)) {
            if ($touch) {
                $update['updated_at'] = date('Y-m-d H:i:s');
            }
            DB::table('admins')->where('id', '=', $adminId)->update($update);
        }
    }

    private function identityColumns(): array
    {
        $cols = ['id', 'name', 'email', 'username', 'status'];
        foreach (['jawatan', 's_id', 'pangkat_id', 'role', 'base_id'] as $col) {
            if (Schema::hasColumn('admins', $col)) {
                $cols[] = $col;
            }
        }
        return $cols;
    }
}
