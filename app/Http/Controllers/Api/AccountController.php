<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\PasswordHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON counterpart to UserChangesController's
 * editEmail/ifEmailAlreadyExists/editPassword.
 */
class AccountController extends Controller
{
    public function updateEmail(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $newEmail = $request->input('newEmail');

        $validator = validator(['newEmail' => $newEmail], ['newEmail' => ['required', 'email']]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first('newEmail')], 422);
        }

        if (DB::table('gen_users')->where('email', '=', $newEmail)->where('id', '!=', $genUser->id)->exists()) {
            return response()->json(['message' => 'This email already exists'], 422);
        }

        DB::table('gen_users')->where('id', '=', $genUser->id)->update(['email' => $newEmail]);

        return response()->json(['message' => 'You have successfully updated your email id.']);
    }

    public function updatePassword(Request $request)
    {
        $genUser = $request->attributes->get('gen_user');
        $oldPassword = $request->input('oldPassword');
        $newPassword = $request->input('newPassword');

        $user = DB::table('gen_users')->where('id', '=', $genUser->id)->first();

        if (!$user || !PasswordHasher::verify($oldPassword, $user->password)) {
            return response()->json(['message' => 'Wrong password.'], 422);
        }

        DB::table('gen_users')->where('id', '=', $genUser->id)->update(['password' => PasswordHasher::make($newPassword)]);

        return response()->json(['message' => 'You have successfully updated your password.']);
    }
}
