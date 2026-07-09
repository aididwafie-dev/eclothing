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
	use App\Models\Unit;
	use Session;

	class AdminUnitController extends Controller
	{
		public function index(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$units = DB::table('units')->get();
			return view('admin/unit/unitList', array("units"=>$units));
		}

		public function editUnit(Request $request, $id) {

			$stringId = base64_decode($id);
			$unit_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$units = DB::table('units')->where('id', '=', $unit_id)->first();
			return view('admin/unit/editUnit', array("units"=>$units));
		}

		public function saveEditedUnit(Request $request) {

			$unit = Unit::find($request->input('id'));
			$unit->value = strtoupper(trim($request->input('value')));
			
			if ($unit->value) {
			$unit->save();

			\Session::flash('message', 'Unit saved successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Unit value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
				
			}
			return redirect()->route('admin.unit');
		}

		public function saveAddedUnit(Request $request) {

			$value = strtoupper(trim($request->input('unit_name')));
			
			if ($value) {
			$unit = new Unit;
			$unit->value = $value;
			$unit->save();
			\Session::flash('message', 'Unit added successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Unit value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
			}
			return redirect()->route('admin.unit');
		}
		
		public function addUnit(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/unit/addUnit');
		}

	}
