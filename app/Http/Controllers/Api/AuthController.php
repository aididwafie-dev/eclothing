<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PasswordHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * JSON counterpart to UserController's login/register/activation/forgot
 * flows -- same PasswordHasher and gen_users rules, Bearer-token session
 * instead of a PHP session.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $sId = $request->input('s_id');
        $password = $request->input('password');

        $user = DB::table('gen_users')
            ->where('s_id', '=', $sId)
            ->where('activation_status', '=', 1)
            ->where('status', '=', 1)
            ->first();

        if (!$user || !PasswordHasher::verify($password, $user->password)) {
            return response()->json(['message' => 'Incorrect Service ID or password.'], 422);
        }

        if (PasswordHasher::needsRehash($user->password)) {
            DB::table('gen_users')->where('id', '=', $user->id)->update(['password' => PasswordHasher::make($password)]);
        }

        $token = Str::random(60);
        DB::table('mobile_api_tokens')->insert([
            'gen_user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'created_at' => now(),
        ]);

        return response()->json([
            'token' => $token,
            'user' => ['id' => $user->id, 's_id' => $user->s_id, 'email' => $user->email],
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            's_id' => ['required', 'digits_between:1,7'],
            'password' => ['required', 'min:8'],
            'confirm_password' => ['required', 'same:password'],
        ]);

        if (DB::table('gen_users')->where('email', '=', $validated['email'])->exists()) {
            return response()->json(['message' => 'Your email address already exists'], 422);
        }
        if (DB::table('gen_users')->where('s_id', '=', $validated['s_id'])->exists()) {
            return response()->json(['message' => 'Your service id already exists'], 422);
        }

        $time = time();
        $authCode = Str::random(8) . $time;

        DB::table('gen_users')->insert([
            'email' => $validated['email'],
            's_id' => $validated['s_id'],
            'password' => PasswordHasher::make($validated['password']),
            'auth_code' => $authCode,
            'status' => 0,
            'activation_status' => 0,
            'profile_status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::raw("Please use this code to activate your account: {$authCode}", function ($message) use ($validated) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($validated['email'])->subject('Activation Code For Personnel Logistic Accounting System');
            });
        } catch (\Throwable $e) {
            // Registration still succeeds; the user can request the code again.
        }

        return response()->json(['message' => 'Registered. Check your email for an activation code.'], 202);
    }

    public function checkAvailability(Request $request)
    {
        $field = $request->query('field');
        $value = $request->query('value');

        if (!in_array($field, ['email', 's_id'], true)) {
            return response()->json(['available' => false, 'message' => 'Invalid field'], 422);
        }

        $exists = DB::table('gen_users')->where($field, '=', $value)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists
                ? ($field === 'email' ? 'Your email address already exists' : 'Your service id already exists')
                : '',
        ]);
    }

    public function activate(string $token)
    {
        $active = DB::table('gen_users')->where('auth_code', '=', $token)->count();

        if ($active > 0) {
            DB::table('gen_users')->where('auth_code', '=', $token)->update([
                'auth_code' => null,
                'status' => 1,
                'activation_status' => 1,
            ]);

            return response()->json(['success' => true, 'message' => 'Your account is now active.']);
        }

        return response()->json(['success' => false, 'message' => 'This activation link is invalid or has expired.'], 422);
    }

    public function forgotPassword(Request $request)
    {
        $email = $request->input('email');

        if (!DB::table('gen_users')->where('email', '=', $email)->exists()) {
            return response()->json(['message' => 'Not a valid email'], 422);
        }

        $newPassword = Str::random(8) . time();

        try {
            Mail::raw("Your new password is: {$newPassword}. We recommend you reset this password as soon as you login.", function ($message) use ($email) {
                $message->from(config('mail.from.address'), config('mail.from.name'));
                $message->to($email)->subject('Personnel Logistic Accounting System Password Recovery');
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Email could not be sent right now. Please contact the administrator.'], 500);
        }

        DB::table('gen_users')->where('email', '=', $email)->update(['password' => PasswordHasher::make($newPassword)]);

        return response()->json(['message' => 'We have sent you an email with your new password.']);
    }

    public function logout(Request $request)
    {
        $tokenHash = hash('sha256', $request->bearerToken());
        DB::table('mobile_api_tokens')->where('token_hash', '=', $tokenHash)->delete();

        // Stop pushing this member's order updates to a handset they just
        // signed out of. Accepted here as well as on DELETE /devices so a
        // logout is enough on its own.
        $deviceToken = trim((string) $request->input('device_token'));
        if ($deviceToken !== '') {
            try {
                DB::table('device_tokens')->where('token', '=', $deviceToken)->delete();
            } catch (\Throwable $e) {
                // Table may not exist yet; logout still succeeds.
            }
        }

        return response()->noContent();
    }
}
