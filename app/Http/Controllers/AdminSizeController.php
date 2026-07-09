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
	use App\Models\Size;
	use Session;

	class AdminSizeController extends Controller
	{
		public function index(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$sizes = DB::table('sizes')->get();
			return view('admin/size/sizeList', array("sizes"=>$sizes));
		}

		public function editSize(Request $request, $id) {

			$stringId = base64_decode($id);
			$size_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$sizes = DB::table('sizes')->where('id', '=', $size_id)->first();
			return view('admin/size/editSize', array("sizes"=>$sizes));
		}

		public function saveEditedSize(Request $request) {

			$size = Size::find($request->input('id'));
			$size->value = strtoupper(trim($request->input('value')));
			
			if ($size->value) {
			$size->save();

			\Session::flash('message', 'Size saved successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Size value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
				
			}
			return redirect()->route('admin.size');
		}

		public function saveAddedSize(Request $request) {

			$value = strtoupper(trim($request->input('size_name')));
			
			if ($value) {
			$size = new Size;
			$size->value = $value;
			$size->save();
			\Session::flash('message', 'Size added successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Size value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
			}
			return redirect()->route('admin.size');
		}
		
		public function addSize(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/size/addSize');
		}

		public function deleteSize(Request $request, $id, $uniform_id) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$size_id = str_replace('DCS','',$stringId);
			DB::table('sizes')->where('id', '=', $size_id)->delete();
			return redirect()->route('admin.size', ['id' => $size_id]);
		}
	}
