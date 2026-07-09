<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AnnouncementsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->session()->get('admin_id') == '') {
            return redirect()->route('site-admin.login');
        }
        return view('admin/announcements');
    }
}
