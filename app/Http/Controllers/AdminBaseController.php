<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use App\Services\AdminBaseService;
	use DB;
	use Session;

	class AdminBaseController extends Controller
	{
		public function index(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$bases = app(AdminBaseService::class)->listWithUnitCounts();
			return view('admin/base/baseList', array("bases"=>$bases));
		}

		public function editBase(Request $request, $id) {

			$stringId = base64_decode($id);
			$base_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$service = app(AdminBaseService::class);
			$base = $service->find((int) $base_id);
			$units = $service->allUnitsWithBase();
			return view('admin/base/editBase', array("base"=>$base, "units"=>$units));
		}

		public function saveEditedBase(Request $request) {

			$result = app(AdminBaseService::class)->update(
				(int) $request->input('id'),
				(string) $request->input('base'),
				$request->input('org_name'),
				$request->input('status'),
				(array) $request->input('unit_ids', [])
			);

			\Session::flash('message', $result['message']);
			\Session::flash('alert-class', $result['ok'] ? 'alert-success' : 'alert-danger');
			return redirect()->route('admin.base');
		}

		public function saveAddedBase(Request $request) {

			$result = app(AdminBaseService::class)->create(
				(string) $request->input('base_name'),
				$request->input('org_name'),
				$request->input('status', 1)
			);

			\Session::flash('message', $result['message']);
			\Session::flash('alert-class', $result['ok'] ? 'alert-success' : 'alert-danger');
			return redirect()->route('admin.base');
		}

		public function addBase(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/base/addBase');
		}

	}
