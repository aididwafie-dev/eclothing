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
	use App\Models\Base;
	use Session;

	class AdminBaseController extends Controller
	{
		public function index(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$bases = DB::table('bases')
				->leftJoin('units', 'units.base_hq', '=', 'bases.id')
				->select('bases.*', DB::raw('COUNT(units.id) as unit_count'))
				->groupBy('bases.id', 'bases.base', 'bases.org_name', 'bases.status', 'bases.created_at', 'bases.updated_at')
				->orderBy('bases.base')
				->get();
			return view('admin/base/baseList', array("bases"=>$bases));
		}

		public function editBase(Request $request, $id) {

			$stringId = base64_decode($id);
			$base_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$base = DB::table('bases')->where('id', '=', $base_id)->first();
			$units = DB::table('units')->orderBy('value')->get();
			return view('admin/base/editBase', array("base"=>$base, "units"=>$units));
		}

		public function saveEditedBase(Request $request) {

			$base = Base::find($request->input('id'));
			$base->base = trim($request->input('base'));
			$base->org_name = trim($request->input('org_name')) ?: null;
			$base->status = $request->input('status');

			if ($base->base) {
				$base->save();
				$this->syncUnits($base->id, $request->input('unit_ids', []));

				\Session::flash('message', 'Base saved successfully');
				\Session::flash('alert-class', 'alert-success');
			} else {
				\Session::flash('message', 'Base name was empty. Please try again');
				\Session::flash('alert-class', 'alert-danger');
			}
			return redirect()->route('admin.base');
		}

		public function saveAddedBase(Request $request) {

			$value = trim($request->input('base_name'));

			if ($value) {
				$base = new Base;
				$base->base = $value;
				$base->org_name = trim($request->input('org_name')) ?: null;
				$base->status = $request->input('status', 1);
				$base->save();
				\Session::flash('message', 'Base added successfully');
				\Session::flash('alert-class', 'alert-success');
			} else {
				\Session::flash('message', 'Base name was empty. Please try again');
				\Session::flash('alert-class', 'alert-danger');
			}
			return redirect()->route('admin.base');
		}

		public function addBase(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/base/addBase');
		}

		/**
		 * The units admin ticked belong to this base; every other unit
		 * currently pointing here is detached. Lets the base's own edit
		 * screen be the one place that assigns its child units.
		 */
		private function syncUnits($baseId, $unitIds) {
			$unitIds = array_filter(array_map('intval', (array) $unitIds));

			DB::table('units')->where('base_hq', '=', $baseId)->update(['base_hq' => null]);
			if (!empty($unitIds)) {
				DB::table('units')->whereIn('id', $unitIds)->update(['base_hq' => $baseId]);
			}
		}

	}
