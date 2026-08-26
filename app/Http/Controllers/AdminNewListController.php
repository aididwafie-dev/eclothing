<?php
	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\Redirect;
	use Illuminate\Support\Facades\Route;
	use App\Http\Requests;
	use DB;
	use Illuminate\Support\Facades\Schema;
	use App\Models\Admin;
	use App\Models\Gen_user;
	use App\Models\Personal_detail;
	use App\Models\Order;
	use App\Models\Uniform_clothe;
	use App\Models\Ordered_clothe;
	use Session;
	use Mail;
	use Illuminate\Mail\Mailer;

	class AdminNewListController extends Controller {

		private function decodeProtectedId($encodedId) {
			$stringId = base64_decode((string) $encodedId);
			return (int) str_replace('DCS', '', (string) $stringId);
		}

		private function encodeProtectedId($id) {
			return base64_encode('DCS' . ((int) $id) . 'DCS');
		}

		private function allPangkats(): array {
			try {
				$rows = DB::table('pangkats')
					->orderBy('pangkats_order', 'asc')
					->orderBy('value', 'asc')
					->get();
				return $rows ? $rows->all() : [];
			} catch (\Throwable $e) {
				return [];
			}
		}

		private function adminIdentityColumns(): array {
			$cols = ['id', 'name', 'email', 'username', 'status'];
			if (Schema::hasTable('admins')) {
				if (Schema::hasColumn('admins', 'jawatan')) {
					$cols[] = 'jawatan';
				}
				if (Schema::hasColumn('admins', 's_id')) {
					$cols[] = 's_id';
				}
				if (Schema::hasColumn('admins', 'pangkat_id')) {
					$cols[] = 'pangkat_id';
				}
			}
			return $cols;
		}

	    public function index(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/new-admin/add_new_admin');
		}

		public function getNewAdminDetails(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$s_id = $request->input('s_id');
			$already_admin = DB::table('admins')->where('username', '=', $s_id)->first();
			if(!empty($already_admin)) {
				\Session::flash('message', 'This user is already a Admin.'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('admin.new-admin');
			}
			$data = [
				'genDetails_newAdmin' => DB::table('gen_users')->where('s_id', '=', $s_id)->first(),
				'personalDetails_newAdmin' => DB::table('personal_details')->where('s_id', '=', $s_id)->first(),
				'pangkats' => $this->allPangkats(),
			];
			if(!empty($data['genDetails_newAdmin'])) {
				if($data['genDetails_newAdmin']->profile_status == 0) {
					\Session::flash('message', 'This user have not filed his personal details.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->route('admin.new-admin');
				}
				else{
					return view('admin/new-admin/viewDetails_before_add',array("data"=>$data));
				}
			}
			else {
				\Session::flash('message', 'Service ID does not exist'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('admin.new-admin');
			}
		}

		public function addNewAdmin(Request $request) {

			$email = $request->input('email');
			$name = $request->input('name');
			$username = $request->input('username');
			$password = $request->input('password');
			$subject = "New Admin for DCS";
			$body = ' '.$name.'! You are added as a admin for the site Personnel Logistic Accounting System. You can login as a admin to the site with the same username and password you use to login as a user. Thank you.';
			try {
				Mail::raw('Hi, '.$body.'',function ($message) use($subject, $email) {
					$message->from(config('mail.from.address'), config('mail.from.name'));
					$message->to($email)->subject($subject);
				});
			} catch (\Throwable $e) {
			}

			$admin = new Admin;
			$admin->name = $request->name;
			$admin->email = $request->email;
			$admin->username = $request->username;
			$admin->password = $request->password;
			$admin->status = 1;
			$admin->save();

			$newAdminId = (int) $admin->id;
			if ($newAdminId > 0 && Schema::hasTable('admins')) {
				$update = [];
				$hasSId = Schema::hasColumn('admins', 's_id');
				$hasPangkat = Schema::hasColumn('admins', 'pangkat_id');
				$hasJawatan = Schema::hasColumn('admins', 'jawatan');
				if ($hasSId) {
					$sId = trim((string) $request->input('s_id'));
					$update['s_id'] = $sId !== '' ? $sId : null;
				}
				if ($hasPangkat) {
					$pangkatId = (int) $request->input('pangkat_id');
					$update['pangkat_id'] = $pangkatId > 0 ? $pangkatId : null;
				}
				if ($hasJawatan) {
					$jawatan = trim((string) $request->input('jawatan'));
					$update['jawatan'] = $jawatan !== '' ? $jawatan : null;
				}
				if (!empty($update)) {
					DB::table('admins')->where('id', '=', $newAdminId)->update($update);
				}
			}

			\Session::flash('message', $name.' have been successfully added as admin.');
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('admin.new-admin');
		}

		public function getAllAdminsList(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/admin-list/List_ofAdmins');
		}

		public function ajaxDatatableAdminsList(Request $request) {

			$identityCols = $this->adminIdentityColumns();
			$select = 'SELECT ' . implode(', ', $identityCols);
			$requestData= $_REQUEST;
			$columns = ['id', 'name', 'email', 'username', 'jawatan_rank', 'status', 'id_edit', 'id_del'];
			$sql = $select . " FROM admins";
			$query=DB::select($sql);
			$totalData = DB::table('admins')->count();
			$totalFiltered = $totalData;

			$sql = $select . " FROM admins";
			
			if( !empty($requestData['search']['value']) ) {
				$sql.=" AND ( id LIKE '".$requestData['search']['value']."%' ";
				$sql.=" OR name LIKE '%".$requestData['search']['value']."%' ";  
				$sql.=" OR email LIKE '%".$requestData['search']['value']."%' ";
				$sql.=" OR username LIKE '%".$requestData['search']['value']."%' )";
			}
			$query=DB::select($sql);
			$totalFiltered = DB::table('admins')->count();

			$sql.=" ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."  LIMIT ".$requestData['start']." ,".$requestData['length']."   ";	
			$query=DB::select($sql);

			$rankMap = [];
			try {
				$rankRows = DB::table('pangkats')->select(['id', 'value'])->get();
				foreach ($rankRows as $rr) {
					$rankMap[(int) $rr->id] = trim((string) ($rr->value ?? ''));
				}
			} catch (\Throwable $e) {
				$rankMap = [];
			}

			$data = array();
			foreach($query as $row_id => $row) {
				$nestedData=array(); 
				$stringId = "DCS".$row->id."DCS";
				$adminID = base64_encode($stringId);

				$serviceId = isset($row->s_id) ? trim((string) $row->s_id) : '';
				$jawatan   = isset($row->jawatan) ? trim((string) $row->jawatan) : '';
				$pangkatId = isset($row->pangkat_id) ? (int) $row->pangkat_id : 0;
				$rankLabel = $pangkatId > 0 && isset($rankMap[$pangkatId]) ? $rankMap[$pangkatId] : '';
				$jrParts = [];
				if ($rankLabel !== '') {
					$jrParts[] = $rankLabel;
				}
				if ($jawatan !== '') {
					$jrParts[] = $jawatan;
				}
				$jrText = implode(' / ', $jrParts);

				$nestedData[] = $row_id+1;
				$nestedData[] = htmlspecialchars($row->name, ENT_QUOTES);
				$nestedData[] = htmlspecialchars($row->email, ENT_QUOTES);
				$nestedData[] = htmlspecialchars($serviceId !== '' ? $serviceId : (string) $row->username, ENT_QUOTES);
				$nestedData[] = htmlspecialchars($jrText, ENT_QUOTES);
				if($row->status == 0) {
					$nestedData[] = "<td><a href='javascript:void(0)' class='btn btn-sm btn-warning admin_active' data-url=".url('change-admin-status/'.$adminID)."><i class='fa fa-unlock-alt' aria-hidden='true'></i> Inactive</a></td>";
				}
				else {
					$nestedData[] = "<td><a href='javascript:void(0)' class='btn btn-sm btn-warning admin_active' data-url=".url('change-admin-status/'.$adminID)."><i class='fa fa-lock' aria-hidden='true'></i> Active</a></td>";
				}
				$nestedData[] = "<a href='".url('/edit/admin_details/'.$adminID)."' class='btn btn-sm btn-info edit_admin'><i class='fa fa-pencil' aria-hidden='true'></i></a>";
				$nestedData[] = "<a href='javascript:void(0)' class='btn btn-sm btn-danger delete_admin' data-url=".url('delete-admin/'.$adminID)."><span class='glyphicon glyphicon-trash'></span></a>";
				$data[] = $nestedData;
			}

			$json_data = array(
						"draw"            => intval( $requestData['draw'] ),
						"recordsTotal"    => intval( $totalData ),
						"recordsFiltered" => intval( $totalFiltered ),
						"data"            => $data
						);
			echo json_encode($json_data);
		}

		public function fromEditAdminDetails(Request $request, $id) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$adminId = $this->decodeProtectedId($id);
			if ($adminId <= 0) {
				\Session::flash('message', 'Invalid admin ID.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('all.admins');
			}
			$cols = $this->adminIdentityColumns();
			$admin = DB::table('admins')
				->where('id', '=', $adminId)
				->select($cols)
				->first();
			if(empty($admin)) {
				\Session::flash('message', 'Admin not found');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('all.admins');
			}
			return view('admin/admin-list/edit_adminDetails', [
				'data' => [
					'admin_id_encoded' => (string) $id,
					'admin' => $admin,
					'pangkats' => $this->allPangkats(),
				],
			]);
		}

		public function changeAdminDetails(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$adminId = $this->decodeProtectedId((string) $request->input('admin_id'));
			if ($adminId <= 0) {
				\Session::flash('message', 'Invalid admin ID.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('all.admins');
			}
			$existing = DB::table('admins')->where('id', '=', $adminId)->first();
			if(empty($existing)) {
				\Session::flash('message', 'Admin not found');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('all.admins');
			}

			if (Schema::hasTable('admins')) {
				$update = [];
				$hasSId = Schema::hasColumn('admins', 's_id');
				$hasPangkat = Schema::hasColumn('admins', 'pangkat_id');
				$hasJawatan = Schema::hasColumn('admins', 'jawatan');
				if ($hasSId) {
					$sId = trim((string) $request->input('s_id'));
					$update['s_id'] = $sId !== '' ? $sId : null;
				}
				if ($hasPangkat) {
					$pangkatId = (int) $request->input('pangkat_id');
					$update['pangkat_id'] = $pangkatId > 0 ? $pangkatId : null;
				}
				if ($hasJawatan) {
					$jawatan = trim((string) $request->input('jawatan'));
					$update['jawatan'] = $jawatan !== '' ? $jawatan : null;
				}
				if (!empty($update)) {
					$update['updated_at'] = date('Y-m-d H:i:s');
					DB::table('admins')->where('id', '=', $adminId)->update($update);
				}
			}

			\Session::flash('message', 'Admin details updated.');
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('all.admins');
		}

		public function changeAdminStatus(Request $request, $id) {

			$stringId = base64_decode($id);
			$admin_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == $admin_id) {
				\Session::flash('message', 'You can not change your status');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('all.admins');
			}
			$user_details = DB::table('admins')->where('id', '=', $admin_id)->first();
			$time = date("Y-m-d H:i:s");
			if($user_details->status == 1) {
				DB::table('admins')->where('id', '=', $admin_id)->update(['status' => 0,'updated_at' => $time]);
			}
			else {
				DB::table('admins')->where('id', '=', $admin_id)->update(['status' => 1,'updated_at' => $time]);
			}
			return redirect()->route('all.admins');
		}
		
		public function deleteUserAsAdmin(Request $request, $id) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$admin_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == $admin_id) {
				\Session::flash('message', 'You can not delete your self'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('all.admins');
			}
			DB::table('admins')->where('id', '=', $admin_id)->delete();
			return redirect()->route('all.admins');
		}
	}
?>
