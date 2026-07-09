<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\Redirect;
	use Illuminate\Support\Facades\Route;
	use App\Http\Requests;
	use DB;
	use App\Models\Admin;
	use App\Models\Gen_user;
	use App\Models\Personal_detail;
	use App\Models\Order;
	use App\Models\Ketukangan;
	use Session;

	class AdminTredController extends Controller
	{
		public function index(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$treds = DB::table('ketukangans')->get();
			return view('admin/tred/tredList', array("treds"=>$treds));
		}

		public function editTred(Request $request, $id) {

			$stringId = base64_decode($id);
			$tred_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$tred = DB::table('ketukangans')->where('id', '=', $tred_id)->first();
			return view('admin/tred/editTred', array("tred"=>$tred));
		}

		public function saveEditedTred(Request $request) {

			$tred = Ketukangan::find($request->input('id'));
			$tred->value = trim($request->input('value'));
			$tred->officer_recruit = $request->input('officer_recruit');
			
			if ($tred->value) {
			$tred->save();

			\Session::flash('message', 'Tred saved successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Tred value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
				
			}
			return redirect()->route('admin.tred');
		}

		public function saveAddedTred(Request $request) {

			$value = trim($request->input('tred_name'));
			
			if ($value) {
			$tred = new Ketukangan;
			$tred->value = $value;
			$tred->officer_recruit = $request->input('officer_recruit');
			$tred->save();
			\Session::flash('message', 'Tred added successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Tred value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
			}
			return redirect()->route('admin.tred');
		}
		
		public function addTred(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/tred/addTred');
		}

	}
