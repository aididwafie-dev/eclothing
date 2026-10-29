<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Support\PasswordHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

    /** AdminPasswordController::ajaxCheckIfValidEmail -- does an admin have this email? */
    public function checkEmail(Request $request)
    {
        return response()->json([
            'valid' => DB::table('admins')->where('email', '=', (string) $request->query('email'))->exists(),
        ]);
    }

    /** AdminPasswordController::ajaxEmailAdminNewPassword -- email a new random password. */
    public function forgotPassword(Request $request)
    {
        $email = (string) $request->input('email');
        if (!DB::table('admins')->where('email', '=', $email)->exists()) {
            return response()->json(['message' => 'Not a valid email'], 422);
        }

        $newPassword = Str::random(8) . time();
        $body = 'Your new password is: ' . $newPassword . '. You can now login using this password. We recommend you to reset this password as soon as you login.';

        try {
            Mail::raw('Hello, admin! ' . $body, function ($message) use ($email) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($email)->subject('Data Collection System Password Recovery of Admin');
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Email could not be sent right now. Please contact the administrator.'], 500);
        }

        DB::table('admins')->where('email', '=', $email)->update(['password' => PasswordHasher::make($newPassword)]);

        return response()->json(['message' => 'We have sent you an email with your new password. Please go back to the login page to login with your new password.']);
    }

    /** JSON counterpart to AdminPasswordController::saveChangePassword. */
    public function updatePassword(Request $request)
    {
        $admin = DB::table('admins')->where('id', '=', $request->attributes->get('admin')->id)->first();

        if (!$admin || !PasswordHasher::verify((string) $request->input('oldPassword'), $admin->password)) {
            return response()->json(['message' => 'Wrong password.'], 422);
        }

        DB::table('admins')->where('id', '=', $admin->id)->update([
            'password' => PasswordHasher::make((string) $request->input('newPassword')),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return response()->json(['message' => 'You have successfully updated your password.']);
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
