<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Loads the reference-table options for the personal-details form, previously
 * duplicated as AdminController::personalDetailsDropdownValues and
 * DashboardController::getDropdownValues.
 */
trait LoadsPersonalDetailDropdowns
{
    public function personalDetailsDropdownValues()
    {
        $piliih_angkatans = DB::table('piliih_angkatans')->get();
        $ketukangans_officer = DB::table('ketukangans')->where('officer_recruit', '=', 1)->get();
        $ketukangans_recruit = DB::table('ketukangans')->where('officer_recruit', '=', 2)->get();
        $ketukangans_both = DB::table('ketukangans')->where('officer_recruit', '=', 3)->get();
        $units = DB::table('units')->get();
        $jantinas = DB::table('jantinas')->get();
        $status_penggunaans = DB::table('status_penggunaans')->get();

        return array(
            'piliih_angkatans' => $piliih_angkatans,
            'ketukangans_officer' => $ketukangans_officer,
            'ketukangans_recruit' => $ketukangans_recruit,
            'ketukangans_both' => $ketukangans_both,
            'units' => $units,
            'jantinas' => $jantinas,
            'status_penggunaans' => $status_penggunaans,
        );
    }
}
