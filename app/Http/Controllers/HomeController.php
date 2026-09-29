<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;

	class HomeController extends Controller
	{
		use \App\Http\Controllers\Concerns\ResolvesAdminRole;

	    public function index(Request $request) {
			
			if($request->session()->get('user_id') != '') {
				return redirect()->route('user.personal');
			}
			else if($request->session()->get('admin_id') != '') {
				return redirect()->route($this->adminHomeRoute($request));
			}
			return view('home');
		}
	}
