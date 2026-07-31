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
	use App\Models\Uniform_clothe;
	use App\Models\Uniform;
	use Session;

	class AdminUniformController extends Controller
	{
		public function index(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$uniforms = DB::table('uniforms')->orderBy("uniform_type")->get();
			return view('admin/uniform/uniformList', array("uniforms"=>$uniforms));
		}

		public function editUniformName(Request $request, $id) {

			$stringId = base64_decode($id);
			$uniform_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$uniforms = DB::table('uniforms')->where('id', '=', $uniform_id)->first();
			return view('admin/uniform/uniform_editName', array("uniforms"=>$uniforms));
		}

		public function saveEditedUniformName(Request $request) {

			$uniforms = Uniform::find($request->input('id'));
			$uniforms->uniform_type = $request->uniform_type;
			$uniforms->uniform_name = $request->uniform_name;
			
				if ($_FILES['uniform_image']) {
					
					$target_file = time() . $_FILES['uniform_image']['name'];

					if (move_uploaded_file($_FILES["uniform_image"]["tmp_name"], base_path('public/uploads/') . $target_file)) {
			$uniforms->uniform_photo = 'uploads/' . $target_file;
						}
				}
			
			
			$uniforms->save();
			return redirect()->route('admin.uniform');
		}

	    public function clothesSummaryToAdmin(Request $request, $id) {

	    	$stringId = base64_decode($id);
	    	$uniform_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$uniforms = DB::table('uniforms')->where('id', '=', $uniform_id)->get();
			$clothes = DB::table('uniform_clothes')->where("uniforms_id", "=", $uniform_id)->where("accessory", "=", 0)->get();
				
				foreach ($clothes as $i => $cloth) {
					//leftJoin("jantinas", "uniform_clothes.jantina", "=", "jantinas.id")->leftJoin("pangkats", "uniform_clothes.pangkat", "=", "pangkats.id")->leftJoin("ketukangans", "uniform_clothes.ketukangan", "=", "ketukangans.id")

					if ($cloth->jantina && count(explode(",", $cloth->jantina))) {
						$jantinas = DB::table("jantinas")->whereIn("id", explode(",", $cloth->jantina))->get();
						
						foreach ($jantinas as $jantina) {
							$clothes[$i]->jantinas_value[] = $jantina->value;
						}
						$clothes[$i]->jantina_value = implode(", ", $clothes[$i]->jantinas_value);
					}
					
					if ($cloth->pangkat && count(explode(",", $cloth->pangkat))) {
						$pangkats = DB::table("pangkats")->whereIn("id", explode(",", $cloth->pangkat))->get();
						
						foreach ($pangkats as $pangkat) {
							$clothes[$i]->pangkats_value[] = $pangkat->value;
						}
						$clothes[$i]->pangkat_value = implode(", ", $clothes[$i]->pangkats_value);
					}
					
					
					if ($cloth->ketukangan && count(explode(",", $cloth->ketukangan))) {
						$ketukangans = DB::table("ketukangans")->whereIn("id", explode(",", $cloth->ketukangan))->get();
						
						foreach ($ketukangans as $ketukangan) {
							$clothes[$i]->ketukangans_value[] = $ketukangan->value;
						}
						$clothes[$i]->ketukangan_value = implode(", ", $clothes[$i]->ketukangans_value);
					}					
				}
			$data = [
				'uniforms' => $uniforms,
				'clothes' => $clothes,
			];
			//dd($data);
			return view('admin/uniform/clothesList', array("data"=>$data));
		}

		public function saveAddedUniform(Request $request) {

			$value = strtoupper(trim($request->input('uniform_type')));
			
			if ($value) {
			$tred = new Uniform;
			$tred->uniform_type = $value;
						$tred->uniform_name = $request->input('uniform_name');

				if ($_FILES['uniform_image']) {
					
					$target_file = time() . $_FILES['uniform_image']['name'];

					if (move_uploaded_file($_FILES["uniform_image"]["tmp_name"], base_path('public/uploads/') . $target_file)) {
			$tred->uniform_photo = 'uploads/' . $target_file;
						}
				}
				
			$tred->save();
			\Session::flash('message', 'Uniform added successfully'); 
			\Session::flash('alert-class', 'alert-success'); 
			} else {
			\Session::flash('message', 'Uniform type value was empty. Please try again'); 
			\Session::flash('alert-class', 'alert-danger'); 
			}
			return redirect()->route('admin.uniform');
		}
		
		public function addUniformClothes(Request $request, $id) {

			$stringId = base64_decode($id);
			$uniform_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$uniforms = DB::table('uniforms')->where('id', '=', $uniform_id)->first();
			return view('admin/uniform/add_clothes', array("uniforms"=>$uniforms, "jantinas" => DB::table('jantinas')->get(), "pangkats" => DB::table('pangkats')->orderBy("value")->get(), "ketukangans" => DB::table('ketukangans')->orderBy("officer_recruit")->orderBy("value")->get()));
		}

		public function saveAddedCloth(Request $request) {

			$uniformsId = strtolower($request->input('id'));
			$clothesType = $uniformsId;
			$clothes_type = strtolower($request->input('clothes_type'));
			$clothesType_array = explode(' ', $clothes_type);
			foreach ($clothesType_array as $key => $value) {
				$clothesType = $clothesType."_".$value;
			}
			$clothesSlug = $clothesType;

			$uniform_clothes = new Uniform_clothe;
			$uniform_clothes->uniforms_id = $request->id;
			$uniform_clothes->clothes_type = $request->clothes_type;
			$uniform_clothes->clothes_slug = $clothesSlug;
			$uniform_clothes->clothes_size = $request->clothes_size;
			
			$uniform_clothes->jantina = $request->jantina ? implode(",", $_POST['jantina']) : null;
			$uniform_clothes->pangkat = $request->pangkat ? implode(",", $_POST['pangkat']) : null;
			$uniform_clothes->ketukangan = $request->ketukangan ? implode(",", $_POST['ketukangan']) : null;
			$uniform_clothes->religion = $request->religion ? implode(",", $_POST['religion']) : null;

			$uniform_clothes->save();
			$stringId = "DCS".$request->input('id')."DCS";
			$uniformsId = base64_encode($stringId);
			return redirect()->route('admin.clothes', ['id' => $uniformsId]);
		}

		public function uniformClothesEditForm(Request $request, $id) {

			$stringId = base64_decode($id);
			$clothes_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$clothes = DB::table('uniform_clothes')->where('id', '=', $clothes_id)->get();
			$uniforms = DB::table('uniforms')->where('id', '=', $clothes['0']->uniforms_id)->first();
			$data = [
				'uniforms' => $uniforms,
				'clothes' => $clothes,
			];
			return view('admin/uniform/editUniform_clothes', array("data"=>$data, "jantinas" => DB::table('jantinas')->get(), "pangkats" => DB::table('pangkats')->orderBy("value")->get(), "ketukangans" => DB::table('ketukangans')->orderBy("officer_recruit")->orderBy("value")->get()));
		}

		public function saveEditedClothes(Request $request) {

			$uniform_clothes = Uniform_clothe::find($request->input('id'));
			$uniform_clothes->clothes_type = $request->clothes_type;
			$uniform_clothes->clothes_size = $request->clothes_size;
			$uniform_clothes->jantina = $request->jantina ? implode(",", $_POST['jantina']) : null;
			$uniform_clothes->pangkat = $request->pangkat ? implode(",", $_POST['pangkat']) : null;
			$uniform_clothes->ketukangan = $request->ketukangan ? implode(",", $_POST['ketukangan']) : null;
			$uniform_clothes->religion = $request->religion ? implode(",", $_POST['religion']) : null;

			$uniform_clothes->save();
			
			$stringId = "DCS".$request->input('uniform_id')."DCS";
			$uniformsId = base64_encode($stringId);
			return redirect()->route('admin.clothes', ['id' => $uniformsId]);
		}
		
		public function addUniform(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/uniform/addUniform');
		}
		
		public function deleteCloth(Request $request, $id, $uniform_id) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$clothes_id = str_replace('DCS','',$stringId);
			$clothes = DB::table('uniform_clothes')->where('id', '=', $clothes_id)->first();
			DB::table('uniform_clothes')->where('id', '=', $clothes_id)->delete();
			DB::table('ordered_clothes')->where('clothes_slug', '=', $clothes->clothes_slug)->delete();
			return redirect()->route('admin.clothes', ['id' => $uniform_id]);
		}
	}
