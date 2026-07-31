<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Loads the signed-in user's record for the sidebar, previously duplicated as
 * DashboardController::checkUserDetails and UserChangesController::checkuserDetails.
 */
trait LoadsSidebarUser
{
    public function checkUserDetails(Request $request)
    {
        $user_id = $request->session()->get('user_id');
        $userDetails = DB::table('gen_users')->where('id', '=', $user_id)->first();
        return $userDetails;
    }
}
