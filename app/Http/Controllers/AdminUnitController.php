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
			$units = DB::table('units')
				->leftJoin('bases', 'bases.id', '=', 'units.base_hq')
				->select('units.*', 'bases.base as base_hq_name')
				->get();
			return view('admin/unit/unitList', array("units"=>$units));
		}

		public function editUnit(Request $request, $id) {

			$stringId = base64_decode($id);
			$unit_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$units = DB::table('units')->where('id', '=', $unit_id)->first();
			$bases = DB::table('bases')->orderBy('base')->get();
			return view('admin/unit/editUnit', array("units"=>$units, "bases"=>$bases));
		}

		public function saveEditedUnit(Request $request) {

			$unit = Unit::find($request->input('id'));
			$unit->value = strtoupper(trim($request->input('value')));
			$this->fillLocation($unit, $request);

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
			$this->fillLocation($unit, $request);
			$unit->save();
			\Session::flash('message', 'Unit added successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Unit value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
			}
			return redirect()->route('admin.unit');
		}
		
		/**
		 * BASE/HQ is a bases.id foreign key (picked from a dropdown); ORG stays
		 * a free-text field, upper-cased like the unit name. Both optional.
		 */
		private function fillLocation(Unit $unit, Request $request) {
			if (!\Illuminate\Support\Facades\Schema::hasColumn('units', 'base_hq')) {
				return;
			}

			$baseId = (int) $request->input('base_hq');
			$unit->base_hq = $baseId > 0 ? $baseId : null;

			$org = mb_substr(strtoupper(trim((string) $request->input('org'))), 0, 255);
			$unit->org = $org !== '' ? $org : null;
		}

		public function addUnit(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$bases = DB::table('bases')->orderBy('base')->get();
			return view('admin/unit/addUnit', array("bases"=>$bases));
		}

	}
