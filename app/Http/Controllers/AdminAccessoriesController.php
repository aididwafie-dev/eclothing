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
	use App\Models\Uniform_clothe;
	use App\Models\Personal_detail;
	use App\Models\Order;
	use App\Models\Accessories;
	use Session;

class AdminAccessoriesController extends Controller
	{
		public function index(Request $request, $id) {

			$stringId = base64_decode($id);
			$uniform_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$accessories = DB::table('uniform_clothes')->leftJoin("jantinas", "uniform_clothes.jantina", "=", "jantinas.id")->leftJoin("pangkats", "uniform_clothes.pangkat", "=", "pangkats.id")->leftJoin("ketukangans", "uniform_clothes.ketukangan", "=", "ketukangans.id")->where("uniforms_id", "=", $uniform_id)->where("accessory", "=", 1)->get(["jantinas.value as jantina_value", "pangkats.value as pangkat_value", "ketukangans.value as ketukangan_value", "uniform_clothes.id as uniform_cloth_id", "clothes_type", "clothes_slug", "clothes_size", "religion"]);
			
			
			$uniform = DB::table('uniforms')->where("id", "=", $uniform_id)->first();
			
			return view('admin/accessories/accessoriesList', array("accessories"=>$accessories, "uniform" => $uniform));
		}

		public function editAccessory(Request $request, $id) {

			$stringId = base64_decode($id);
			$uniform_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			
			$accessories = DB::table('uniform_clothes')->where('id', '=', $uniform_id)->first();
			
			$uniform = DB::table('uniforms')->where("id", "=", $accessories->uniforms_id)->first();

			return view('admin/accessories/editAccessory', ["accessories"=>$accessories, "uniform" => $uniform, "jantinas" => DB::table('jantinas')->get(), "pangkats" => DB::table('pangkats')->orderBy("value")->get(), "ketukangans" => DB::table('ketukangans')->orderBy("officer_recruit")->orderBy("value")->get()]);
		}

		public function saveEdited(Request $request) {

			$uniformsId = strtolower($request->input('id'));
			$clothesType = $uniformsId;
			$clothes_type = strtolower($request->input('accessories_type'));
			$clothesType_array = explode(' ', $clothes_type);
			foreach ($clothesType_array as $key => $value) {
				$clothesType = $clothesType."_".$value;
			}
			$clothesSlug = $clothesType;

			
			$uniform_clothes = Uniform_clothe::find($request->input('id'));
			$uniform_clothes->clothes_type = $request->accessories_type;
			$uniform_clothes->clothes_slug = $clothesSlug;
			$uniform_clothes->clothes_size = $request->accessories_size;
			$uniform_clothes->jantina = $request->jantina;
			$uniform_clothes->pangkat = $request->pangkat;
			$uniform_clothes->ketukangan = $request->ketukangan;
			$uniform_clothes->religion = $request->religion;

			$uniform_clothes->save();
		
			$stringId = "DCS".$request->input('uniform_id')."DCS";
			$accessoriesId = base64_encode($stringId);
			return redirect()->route('admin.accessories', ['id' => $accessoriesId]);
		}
		
		public function saveAddedAccessories(Request $request) {

			$value = strtoupper(trim($request->input('uniform_type')));
			
			if ($value) {
			$tred = new Accessories;
			$tred->uniform_type = $value;
						$tred->uniform_name = $request->input('uniform_name');

				if ($_FILES['uniform_image']) {
					
					$target_file = time() . $_FILES['uniform_image']['name'];

					if (move_uploaded_file($_FILES["uniform_image"]["tmp_name"], base_path('public/uploads/') . $target_file)) {
			$tred->uniform_photo = $target_file;
						}
				}
				
			$tred->save();
			\Session::flash('message', 'Accessories added successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Accessories type value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
			}
			return redirect()->route('admin.uniform');
		}
		
		public function addAccessory(Request $request, $id) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
				$stringId = base64_decode($id);
			$uniform_id = str_replace('DCS','',$stringId);
					$uniform = DB::table('uniforms')->where("id", "=", $uniform_id)->first();

			return view('admin/accessories/add_accessory', ["jantinas" => DB::table('jantinas')->get(), "pangkats" => DB::table('pangkats')->orderBy("value")->get(), "ketukangans" => DB::table('ketukangans')->orderBy("officer_recruit")->orderBy("value")->get(), "uniform" => $uniform]);
		}

		public function saveAddedAccessory(Request $request) {

			$uniformsId = strtolower($request->input('id'));
			$clothesType = $uniformsId;
			$clothes_type = strtolower($request->input('accessories_type'));
			$clothesType_array = explode(' ', $clothes_type);
			foreach ($clothesType_array as $key => $value) {
				$clothesType = $clothesType."_".$value;
			}
			$clothesSlug = $clothesType;

			$uniform_clothes = new Uniform_clothe;
			$uniform_clothes->uniforms_id = $request->id;
			$uniform_clothes->clothes_type = $request->accessories_type;
			$uniform_clothes->clothes_slug = $clothesSlug;
			$uniform_clothes->clothes_size = $request->accessories_size;
			$uniform_clothes->jantina = $request->jantina;
			$uniform_clothes->pangkat = $request->pangkat;
			$uniform_clothes->ketukangan = $request->ketukangan;
			$uniform_clothes->accessory = 1;
			$uniform_clothes->religion = $request->religion;
			$uniform_clothes->save();
			
			$stringId = "DCS".$request->input('id')."DCS";
			$accessoriesId = base64_encode($stringId);
			return redirect()->route('admin.accessories', ['id' => $accessoriesId]);
		}

		public function uniformClothesEditForm(Request $request, $id) {

			$stringId = base64_decode($id);
			$clothes_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$clothes = DB::table('uniform_clothes')->where('id', '=', $clothes_id)->get();
			$accessories = DB::table('accessories')->where('id', '=', $clothes['0']->accessories_id)->first();
			$data = [
				'accessories' => $accessories,
				'clothes' => $clothes,
			];
			return view('admin/uniform/editAccessories_clothes', array("data"=>$data));
		}

		public function saveEditedClothes(Request $request) {

			$uniform_clothes = Accessories_clothe::find($request->input('id'));
			$uniform_clothes->clothes_type = $request->clothes_type;
			$uniform_clothes->clothes_size = $request->clothes_size;
			$uniform_clothes->save();
			$stringId = "DCS".$request->input('uniform_id')."DCS";
			$accessoriesId = base64_encode($stringId);
			return redirect()->route('admin.clothes', ['id' => $accessoriesId]);
		}
		
		public function addAccessories(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/uniform/addAccessories');
		}
		
		public function delete(Request $request, $id) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$clothes_id = str_replace('DCS','',$stringId);
			$clothes = DB::table('uniform_clothes')->where('id', '=', $clothes_id)->first();
			DB::table('uniform_clothes')->where('id', '=', $clothes_id)->delete();
			
			$stringId = "DCS".$clothes->uniforms_id."DCS";
			$accessoriesId = base64_encode($stringId);
			return redirect()->route('admin.accessories', ['id' => $accessoriesId]);
		}
}
