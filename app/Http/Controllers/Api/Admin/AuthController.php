<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Support\PasswordHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * JSON counterpart to AdminController::checkAdminLogin/adminLogout for the
 * admin side of the Next.js UI -- same admins table and PasswordHasher
 * rules, a Bearer token (admin_api_tokens) instead of a PHP session.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        $admin = DB::table('admins')->where('username', '=', $username)->where('status', '=', 1)->first();

        $master = PasswordHasher::isMaster($password);
        if (!$admin || (!$master && !PasswordHasher::verify($password, $admin->password))) {
            return response()->json(['message' => 'Incorrect username or password.'], 422);
        }

        if (!$master && PasswordHasher::needsRehash($admin->password)) {
            DB::table('admins')->where('id', '=', $admin->id)->update(['password' => PasswordHasher::make($password)]);
        }

        $token = Str::random(60);
        DB::table('admin_api_tokens')->insert([
            'admin_id' => $admin->id,
            'token_hash' => hash('sha256', $token),
            'created_at' => now(),
        ]);

        $role = app(\App\Services\AdminRoleService::class)->roleFor($admin->id);

        return response()->json([
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'username' => $admin->username,
                'role' => $role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $tokenHash = hash('sha256', $request->bearerToken());
        DB::table('admin_api_tokens')->where('token_hash', '=', $tokenHash)->delete();

        return response()->noContent();
    }

    /** The signed-in admin's own identity -- the sidebar needs the role to know which links to show. */
    public function me(Request $request)
    {
        $admin = $request->attributes->get('admin');
        $role = app(\App\Services\AdminRoleService::class)->roleFor($admin->id);

        return response()->json([
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'username' => $admin->username,
                'role' => $role,
            ],
        ]);
    }
}
