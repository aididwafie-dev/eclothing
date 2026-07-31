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
	use App\Models\Ordered_clothe;
	use Session;
	use Mail;
	use Illuminate\Mail\Mailer;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Facades\Schema;
	use App\Support\PasswordHasher;

	class AdminController extends Controller
	{
		use \App\Http\Controllers\Concerns\ResolvesOrderStatus;
		use \App\Http\Controllers\Concerns\LoadsPersonalDetailDropdowns;

	    public function index(Request $request) {
				
			if($request->session()->get('admin_id') != '')
			{
				return redirect()->route('admin.new-admin');
			}
			return view('admin/admin_login');
		}

		public function checkAdminLogin(Request $request) {

			$username = $request->input('username');
			$password = $request->input('password');
			$log = DB::table('admins')->where('username', '=', $username)->first();
			if(!empty($log) && PasswordHasher::verify($password, $log->password)) {
				if (PasswordHasher::needsRehash($log->password)) {
					DB::table('admins')->where('id', '=', $log->id)->update(['password' => PasswordHasher::make($password)]);
				}
				$admin_id = $log->id;
				$request->session()->put('admin_id', $admin_id);
				return redirect()->route('admin.new-admin');
			}
			else{
				\Session::flash('message', 'You can not login.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('site-admin.login');
			}
		}

		public function adminLogout(Request $request) {

			$request->session()->flush();
			$request->session()->put('admin_id', '');
			\Session::flash('message', 'You have successfully logged out.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('home');
		}

		public function allUsersTable(Request $request) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			return view('admin/all_user_table');
		}

		public function adminCancel() {

			return redirect()->route('all.users');
		}

		private function decodeProtectedId($encodedId) {
			$stringId = base64_decode($encodedId);
			return str_replace('DCS','',$stringId);
		}

		private function encodeProtectedId($id) {
			return base64_encode('DCS'.$id.'DCS');
		}

		private function hasUniformClothesPhotoColumn() {
			static $hasUniformClothesPhotoColumn = null;

			if ($hasUniformClothesPhotoColumn !== null) {
				return $hasUniformClothesPhotoColumn;
			}

			try {
				$hasUniformClothesPhotoColumn = Schema::hasTable('uniform_clothes')
					&& Schema::hasColumn('uniform_clothes', 'clothes_photo');
			} catch (\Throwable $e) {
				$hasUniformClothesPhotoColumn = false;
			}

			return $hasUniformClothesPhotoColumn;
		}

		private function uniformOrdersListQuery() {
			$itemCounts = DB::table('ordered_clothes')
				->select('order_id', DB::raw('COUNT(*) as items_count'))
				->groupBy('order_id');

			$selectColumns = [
				'orders.id',
				'orders.user_id',
				'orders.uniforms_id',
				'orders.created_at',
				'orders.updated_at',
				'gen_users.s_id',
				'personal_details.name',
				'units.value as unit_name',
				'uniforms.uniform_type',
				'uniforms.uniform_name',
				DB::raw('COALESCE(order_items.items_count, 0) as items_count'),
			];

			if ($this->orderStatus()->hasOrderLifecycleColumns()) {
				$selectColumns[] = 'orders.status';
				$selectColumns[] = 'orders.remarks';
				$selectColumns[] = 'orders.collection_date';
			} else {
				$selectColumns[] = DB::raw("'1' as status");
				$selectColumns[] = DB::raw('NULL as remarks');
				$selectColumns[] = DB::raw('NULL as collection_date');
			}

			return DB::table('orders')
				->leftJoin('gen_users', 'orders.user_id', '=', 'gen_users.id')
				->leftJoin('personal_details', 'orders.user_id', '=', 'personal_details.user_id')
				->leftJoin('units', 'personal_details.unit', '=', 'units.id')
				->leftJoin('uniforms', 'orders.uniforms_id', '=', 'uniforms.id')
				->leftJoinSub($itemCounts, 'order_items', function ($join) {
					$join->on('order_items.order_id', '=', 'orders.id');
				})
				->select($selectColumns)
				->where('orders.deleted', '=', 0);
		}

		public function ajaxDatatableUsersDetails(Request $request) {

			$requestData= $_REQUEST;

			$columns = array(
				0=> 'id',
				1=> 's_id',
				2=> 'pangkat',
				3=> 'name',
				4=> 'unit',
				5=> 'id',
				6=> 'id',
				7=> 'id',
				8=> 'status',
				9=> 'id',
				10=> 'id',
				11=> 'created_at',
				12=> 'updated_at',
			);

			$sql = "SELECT gen_users.id, gen_users.s_id, gen_users.status, gen_users.activation_status, gen_users.profile_status, personal_details.name, personal_details.unit, personal_details.pangkat, personal_details.created_at, personal_details.updated_at FROM gen_users LEFT JOIN personal_details ON gen_users.id=personal_details.user_id";
			$query=DB::select($sql);
			$totalData = DB::table('gen_users')->count();
			$totalFiltered = $totalData;

			$sql.=" WHERE 1=1";
			if (!empty($requestData['filter'])) {
				$filter = $requestData['filter'];
				if ($filter == 'incomplete_rank') {
					$sql.=" AND (personal_details.pangkat IS NULL OR personal_details.pangkat = '')";
				} else if ($filter == 'incomplete_name') {
					$sql.=" AND (personal_details.name IS NULL OR personal_details.name = '')";
				} else if ($filter == 'incomplete_unit') {
					$sql.=" AND (personal_details.unit IS NULL OR personal_details.unit = '')";
				} else if ($filter == 'incomplete_personal') {
					$sql.=" AND (gen_users.profile_status IS NULL OR gen_users.profile_status <> 1)";
				} else if ($filter == 'incomplete_uniform') {
					$sql.=" AND NOT EXISTS (SELECT 1 FROM orders WHERE orders.deleted = 0 AND orders.user_id = gen_users.id)";
				} else if ($filter == 'complete_all') {
					$sql.=" AND personal_details.pangkat IS NOT NULL AND personal_details.pangkat <> ''";
					$sql.=" AND personal_details.name IS NOT NULL AND personal_details.name <> ''";
					$sql.=" AND personal_details.unit IS NOT NULL AND personal_details.unit <> ''";
					$sql.=" AND gen_users.profile_status = 1";
					$sql.=" AND EXISTS (SELECT 1 FROM orders WHERE orders.deleted = 0 AND orders.user_id = gen_users.id)";
				}
			}
			if( !empty($requestData['search']['value']) ) {
				$sql.=" AND ( personal_details.name LIKE '%".$requestData['search']['value']."%' ";    
				$sql.=" OR personal_details.pangkat LIKE '%".$requestData['search']['value']."%' ";
				$sql.=" OR personal_details.unit LIKE '%".$requestData['search']['value']."%' ";
				$sql.=" OR gen_users.s_id LIKE '%".$requestData['search']['value']."%' )";
			}
			$query=DB::select($sql);
			$totalFiltered = DB::table('gen_users')->count();

			$sql.=" ORDER BY ". $columns[$requestData['order'][0]['column']]."   ".$requestData['order'][0]['dir']."  LIMIT ".$requestData['start']." ,".$requestData['length']."   ";	
			$query=DB::select($sql);

			$data = array();
			foreach($query as $row_id => $row) {

				$stringId = "DCS".$row->id."DCS";
				$userId = base64_encode($stringId);

				$nestedData=array();
					$nestedData[] = $requestData['start'] + $row_id+1;
				
					// Marks whether the user has ever ordered. "No orders" is a neutral
					// fact, not a warning, so it uses the neutral tone rather than the
					// gold reserved for pending states. Both carry an icon and a title
					// so the distinction is not conveyed by colour alone.
					$orders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $row->id)->first();
					if ($orders && $orders->id) {
				$nestedData[] = '<span class="cell-id cell-id-ordered" title="Has placed orders">' . $row->s_id . '</span>';
					} else {
				$nestedData[] = '<span class="cell-id cell-id-none" title="No orders yet">' . $row->s_id . '</span>';
					}
				
				if(!empty($row->pangkat)) {
					$pangkats = DB::table('pangkats')->where('id', '=', $row->pangkat)->first();
					$nestedData[] = $pangkats->value;
				}
				else {
					$nestedData[] = "";
				}
				$nestedData[] = $row->name;
				if(!empty($row->unit)) {
					$units = DB::table('units')->where('id', '=', $row->unit)->first();
					if ($units) {
					$nestedData[] = $units->value;
					} else {
						$nestedData[] = "N/A";
					}
				}
				else {
					$nestedData[] = "";
				}
				$nestedData[] = "<a href=".url('edit/basic_details/'.$userId)." class='btn btn-sm btn-primary'><span class='glyphicon glyphicon-pencil'></span></a>";
				$nestedData[] = "<a href=".url('edit/personal_details/'.$userId)." class='btn btn-sm btn-primary'><span class='glyphicon glyphicon-pencil'></span></a>";
				$nestedData[] = "<a href=".url('show/uniform_details/'.$userId)." class='btn btn-sm btn-info'><span class='glyphicon glyphicon-folder-open'></span> Show</a>";
				if($row->status == 0) {
					$nestedData[] = "<td><a href='javascript:void(0)' class='block_unblock btn btn-sm btn-warning' data-url=".url('change-status/'.$userId)."><i class='fa fa-unlock-alt' aria-hidden='true'></i> Unblock</a></td>";
				}
				else {
					$nestedData[] = "<td><a href='javascript:void(0)' class='block_unblock btn btn-sm btn-warning' data-url=".url('change-status/'.$userId)."><i class='fa fa-lock' aria-hidden='true'></i> Block</a></td>";
				}
				$nestedData[] = "<a href='javascript:void(0)' class='btn btn-sm btn-danger delete_user' data-url=".url('delete-user/'.$userId)."><span class='glyphicon glyphicon-trash'></span></a>";
				
$nestedData[] = $row->created_at;
$nestedData[] = $row->updated_at;
				if($row->activation_status == 0) {
					$nestedData[] = "<a class='resend_mail btn btn-sm btn-success' data-userId=".$userId." ><i class='fa fa-share' aria-hidden='true'></i></a>";
				}
				else {
					$nestedData[] = "Already active";
				}
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

		public function fromEditUserBasicDetails(Request $request, $id) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$user_id = str_replace('DCS','',$stringId);
			$user_details = DB::table('gen_users')->where('id', '=', $user_id)->first();
			return view('admin/basicDetails_formAdmin', array("user_details"=>$user_details));
		}

		public function sendAnnouncement(Request $request) {
			
			$subject = $request->input("subject");
			
			$body = $request->input("body");

			if ($request->input("send_to") == "all") {
				$emails = DB::table('gen_users')->where('status', '=', 1)->get();
			} else if ($request->input("send_to") == "unconfirmed") {
				$emails = DB::table('gen_users')->where('status', '=', 1)->where("activation_status", "=", 0)->get();
			} else if ($request->input("send_to") == "confirmed") {
				$emails = DB::table('gen_users')->where('status', '=', 1)->where("activation_status", "=", 1)->get();
			} else if ($request->input("send_to") == "no_orders") {
				$users = DB::table('gen_users')->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")->leftJoin("units", "units.id", "=", "personal_details.unit")->where('status', '=', 1);
			
				$users = $users->get();

				$users_result = [];

				foreach ($users as $user) {
					 $orders = DB::table('orders')->where('user_id', '=', $user->id)->first();

					if (!$orders && $user && $user->s_id) {
						$emails[] = $user;
					}
				}			
			}
			
			//activation_status == 0
			
			$sent_to = 0;
			
			if ($emails && count($emails)) {
				
				foreach ($emails as $email) {
			
					$to = $email->email;
					
					$from = $request->input("from");
					
					try {
						Mail::raw($body,function ($message) use($subject, $from, $to) {
							$message->from($from, 'Personnel Logistic Accounting System');
							$message->to($to)->subject($subject);
						});
						$sent_to++;
					} catch (\Throwable $e) {
					}
				}
				
			}
			
			\Session::flash('message', 'Email sent to ' . $sent_to . ' users.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('admin.announcements');
			
		}
		
		public function changeBasicDetails(Request $request) {

		 	$genuser = Gen_user::find($request->input('id'));
		 	if ($request->password !== $genuser->password) {
		 		$genuser->password = PasswordHasher::make($request->password);
		 	}
		 	$genuser->s_id = $request->s_id;
		 	$genuser->save();
		 	$personalDetails = DB::table('personal_details')->where('user_id', '=', $request->input('id'))->first();
			if(!empty($personalDetails)) {
				$personal_detail = Personal_detail::find($personalDetails->id);
				$personal_detail->s_id = $request->s_id;
				$personal_detail->save();
			}
		 	return redirect()->route('all.users');
		}

		public function fromEditUserPersonalDetails(Request $request, $id) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$user_id = str_replace('DCS','',$stringId);
			$user_personalDetails = DB::table('personal_details')->where('user_id', '=', $user_id)->first();
			$data = [
				'personal_detail' => $user_personalDetails,
				'dropdown_data' => $this->personalDetailsDropdownValues(),
			];
			return view('admin/personalDetails_formAdmin', array("data"=>$data));
		}

		public function ajaxLoadRankValuesForAdmin(Request $request) {
			
			$service_id = $request->input('serviceId');
			$tred_type = $request->input('tredType');
			$user_id = $request->input('userId');
			$personal_detail = DB::table('personal_details')->where('user_id', '=', $user_id)->first();
			$pangkats = DB::table('pangkats')->where('officer_recruit', '=', $tred_type)->where('piliih_angkatan_id', '=', $service_id)->get();
			return view('rank_dropdown_values',array('pangkats'=>$pangkats,'personal_detail'=>$personal_detail)); //loading the same page for both Admin and User
		}

		public function changePersonalDetails(Request $request) {

			$address_array = [
				0 => $request->address1,
				1 => $request->address2,
				2 => $request->address3,
				3 => $request->address4,
			];
			$address = implode($address_array, "|");
			if($request->ketukangans_type == 1) {
				$ketukangans_type = 1;
				$ketukangan = $request->ketukangans_officer;
			}
			else {
				$ketukangans_type = 2;
				$ketukangan = $request->ketukangans_recruit;
			}
			$detail = DB::table('personal_details')->where('user_id', '=', $request->input('user_id'))->first(); //getting id of the row, where user_id maches from personal_details table.

			$personal_detail = Personal_detail::find($detail->id);

			$personal_detail->user_id = $request->input('user_id');
			$personal_detail->s_id = $request->s_id;
			$personal_detail->name = $request->name;
			$personal_detail->piliih_angkatan = $request->piliih_angkatan;
			$personal_detail->pangkat = $request->pangkat;
			$personal_detail->ketukangan_type = $ketukangans_type;
			$personal_detail->ketukangan = $ketukangan;
			$personal_detail->unit = $request->unit;
			$personal_detail->jantina = $request->jantina;
			$personal_detail->telephone_number = $request->telephone_number;
			$personal_detail->address = $address;
			$personal_detail->nama_waris = $request->nama_waris;
			$personal_detail->telephone_number_waris = $request->tele_number_waris;
			$personal_detail->status_penggunaan = $request->status_penggunaan;
			$personal_detail->unit_lama = $request->unit_lama;
			$personal_detail->name_tag = $request->name_tag;
			$personal_detail->religion = $request->status_religion ? $request->status_religion : $request->status_religion_others;
			
			$personal_detail->kem_lama = $request->kem_lama;
			$personal_detail->spl_lama = $request->spl_lama;
			$personal_detail->save();

			return redirect()->route('all.users');
		}

		public function changeUserAccessStatus(Request $request, $id) {

			$stringId = base64_decode($id);
			$user_id = str_replace('DCS','',$stringId);
			$user_details = DB::table('gen_users')->where('id', '=', $user_id)->first();
			$time = date("Y-m-d H:i:s");
			if($user_details->status == 1) {
				DB::table('gen_users')->where('id', '=', $user_id)->update(['status' => 0]);
			}
			else {
				DB::table('gen_users')->where('id', '=', $user_id)->update(['status' => 1]);
			}
			return redirect()->route('all.users');
		}

		public function changeUniformEnableDisable(Request $request, $id) {

			$stringId = base64_decode($id);
			$user_id = str_replace('DCS','',$stringId);
			$details = DB::table('uniforms')->where('id', '=', $user_id)->first();
			$time = date("Y-m-d H:i:s");
			if($details->active == 1) {
				DB::table('uniforms')->where('id', '=', $user_id)->update(['active' => 0,'updated_at' => $time]);
			}
			else {
				DB::table('uniforms')->where('id', '=', $user_id)->update(['active' => 1,'updated_at' => $time]);
			}
			return redirect()->route('admin.uniform');
		}

		public function changeUserAccessBlockAll(Request $request) {
			$time = date("Y-m-d H:i:s");
			DB::table('gen_users')->where('status', '=', 1)->update(['status' => 0]);
			\Session::flash('message', 'All active users are now blocked.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('all.users');
		}

		public function changeUserAccessUnblockAll(Request $request) {
			$time = date("Y-m-d H:i:s");
			DB::table('gen_users')->where('status', '=', 0)->update(['status' => 1]);
			\Session::flash('message', 'All active users are now blocked.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('all.users');
		}

		public function listUserUniformDetails(Request $request, $id) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$user_id = str_replace('DCS','',$stringId);
			$checkIf_userOrdered = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $user_id)->first();

			if(!empty($checkIf_userOrdered)) {
				$user_order = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $user_id)->get();

				$i = 0;
				foreach ($user_order as $userOrder) {
					$userOrder = $this->orderStatus()->normalizeOrderLifecycle($userOrder);
					$uniform_type = DB::table('uniforms')->where('id', '=', $userOrder->uniforms_id)->first();
					$ordered_clothes = DB::table('ordered_clothes')->where('order_id', '=', $userOrder->id)->get();
					$data[$i] = [
						'user_order' => $userOrder,
						'uniform_type' => $uniform_type,
						'ordered_clothes' => $ordered_clothes,
					];
					$i++;
				}
			}
			else {
				$data = 0;
			}

			return view('admin/uniformDetails_eachUser', array("data"=>$data));
		}

		public function uniformOrdersList(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}

			/*
			Legacy query reference kept for future comparison/debugging.
			$orders = DB::table('orders')
				->leftJoin('gen_users', 'orders.user_id', '=', 'gen_users.id')
				->leftJoin('personal_details', 'orders.user_id', '=', 'personal_details.user_id')
				->leftJoin('units', 'personal_details.unit', '=', 'units.id')
				->leftJoin('uniforms', 'orders.uniforms_id', '=', 'uniforms.id')
				->select(
					'orders.*',
					'gen_users.s_id',
					'personal_details.name',
					'units.value as unit_name',
					'uniforms.uniform_type',
					'uniforms.uniform_name',
					DB::raw('(SELECT COUNT(*) FROM ordered_clothes WHERE ordered_clothes.order_id = orders.id) as items_count')
				)
				->where('orders.deleted', '=', 0)
				->orderBy('orders.created_at', 'desc')
				->get();
			*/

			// Search by Order ID. Tolerate a leading '#' and stray spacing; an
			// empty search shows the full list. A non-empty search with no digits
			// resolves to id 0, which matches nothing -> "not found".
			$search = trim((string) $request->query('search', ''));
			$hasSearch = $search !== '';

			$query = $this->uniformOrdersListQuery();
			if ($hasSearch) {
				$query->where('orders.id', '=', (int) preg_replace('/\D+/', '', $search));
			}

			$orders = $query
				->orderBy('orders.created_at', 'desc')
				->orderBy('orders.id', 'desc')
				->simplePaginate(25);

			if ($hasSearch) {
				$orders->appends(['search' => $search]);
			}

			$orders->getCollection()->transform(function ($order) {
				return $this->orderStatus()->normalizeOrderLifecycle($order);
			});

			return view('admin/uniform_orders_list', array('orders' => $orders, 'search' => $search));
		}

		public function uniformOrderDetail(Request $request, $id) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}

			$order_id = $this->decodeProtectedId($id);

			$order = DB::table('orders')
				->leftJoin('gen_users', 'orders.user_id', '=', 'gen_users.id')
				->leftJoin('personal_details', 'orders.user_id', '=', 'personal_details.user_id')
				->leftJoin('units', 'personal_details.unit', '=', 'units.id')
				->leftJoin('uniforms', 'orders.uniforms_id', '=', 'uniforms.id')
				->select(
					'orders.*',
					'gen_users.s_id',
					'gen_users.email',
					'personal_details.name',
					'units.value as unit_name',
					'uniforms.uniform_type',
					'uniforms.uniform_name',
					'uniforms.uniform_photo'
				)
				->where('orders.deleted', '=', 0)
				->where('orders.id', '=', $order_id)
				->first();

			if (empty($order)) {
				\Session::flash('message', 'Order not found.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('admin.uniform-orders');
			}

			$order = $this->orderStatus()->normalizeOrderLifecycle($order);
			$ordered_clothes = DB::table('ordered_clothes')->where('order_id', '=', $order_id)->get();

			return view('admin/uniform_order_detail', array(
				'order' => $order,
				'ordered_clothes' => $ordered_clothes,
			));
		}

		public function updateUniformOrderStatus(Request $request) {

			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}

			if (!$this->orderStatus()->hasOrderLifecycleColumns()) {
				\Session::flash('message', 'Please run the latest migration before updating order status.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->back();
			}

			$validator = Validator::make($request->all(), [
				'order_id' => 'required|integer',
				'status' => 'required|in:1,2,3,4',
				'remarks' => 'nullable|string|max:1000',
				'collection_date' => 'nullable|date',
			]);

			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->withInput();
			}

			$order = DB::table('orders')
				->where('deleted', '=', 0)
				->where('id', '=', $request->input('order_id'))
				->first();

			if (empty($order)) {
				\Session::flash('message', 'Order not found.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('admin.uniform-orders');
			}

			$status = trim((string) $request->input('status'));
			$remarks = trim((string) $request->input('remarks'));
			$collectionDate = $request->input('collection_date');

			$updateData = [
				'status' => $status,
				'remarks' => $remarks !== '' ? $remarks : null,
				'collection_date' => $collectionDate ? date('Y-m-d', strtotime($collectionDate)) : null,
				'updated_at' => date("Y-m-d H:i:s"),
			];

			if (in_array($status, ['2', '4'])) {
				$updateData['collection_date'] = null;
			}

			DB::table('orders')->where('id', '=', $order->id)->update($updateData);

			\Session::flash('message', 'Order status updated successfully.');
			\Session::flash('alert-class', 'alert-success');

			return redirect()->route('admin.uniform-orders.show', ['id' => $this->encodeProtectedId($order->id)]);
		}

		public function fromEditUserUniformDetails(Request $request, $id) {

			$stringId = base64_decode($id);
			$order_id = str_replace('DCS','',$stringId);
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$userOrder = DB::table('orders')->where('deleted', '=', 0)->where('id', '=', $order_id)->first();
			$uniforms = DB::table('uniforms')->where('id', '=', $userOrder->uniforms_id)->first();
			$ordered_clothes = DB::table('ordered_clothes')->where('order_id', '=', $order_id)->get();
			return view('admin/uniformDetails_edit', array("uniforms"=>$uniforms,"userOrder"=>$userOrder,"ordered_clothes"=>$ordered_clothes));
		}

		public function saveUniformEditedDetails(Request $request) {

			$order_id = $request->input('order_id');

			$orderedClothes = DB::table('ordered_clothes')->where('order_id', '=', $order_id)->get();

			foreach ($orderedClothes as $cloth) {

				foreach ($_POST as $key => $value) {

					if($key == $cloth->clothes_slug) {

						$ordered_clothes = Ordered_clothe::find($cloth->id);

						if($key != 'submit' && $key != '_token' && $key != 'order_id' && $key != 'user_id'){

							$ordered_clothes->clothes_slug = $key;
							$ordered_clothes->size = $request->$key;
						}
					}
				}
				$ordered_clothes->save();
			}
			return redirect()->route('all.users');
		}
		
		public function deleteGeneralUser(Request $request, $id) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$user_id = str_replace('DCS','',$stringId);
			
			DB::table('gen_users')->where('id', '=', $user_id)->delete();
			$personal_details = DB::table('personal_details')->where('user_id', '=', $user_id)->first();
			if(!empty($personal_details)) {
				DB::table('personal_details')->where('user_id', '=', $user_id)->delete();
			}
			return redirect()->route('all.users');
		}
		
		
		public function deleteUnit(Request $request, $id) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$unit_id = str_replace('DCS','',$stringId);
			DB::table('units')->where('id', '=', $unit_id)->delete();
			\Session::flash('message', 'The unit was successfully deleted.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('admin.unit', ['id' => $unit_id]);
		}
		
		public function deleteTred(Request $request, $id) {
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$tred_id = str_replace('DCS','',$stringId);
			DB::table('ketukangans')->where('id', '=', $tred_id)->delete();
			return redirect()->route('admin.tred', ['id' => $tred_id]);
		}
		
		public function deleteOrder(Request $request, $user_id, $id) {
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($id);
			$order_id = str_replace('DCS','',$stringId);

			DB::table('orders')->where('deleted', '=', 0)->where('id', '=', $order_id)->delete();
			DB::table('ordered_clothes')->where('order_id', '=', $order_id)->delete();
			return redirect()->route('show.uniform_details', ['id' => $user_id]);
		}
		
		public function resendActivationMailToUser(Request $request) {
			
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}
			$stringId = base64_decode($request->input('userId'));
			$user_id = str_replace('DCS','',$stringId);
			$user_details = DB::table('gen_users')->where('id', '=', $user_id)->first();
			$auth_code = $user_details->auth_code;
			$email = $user_details->email;
			$subject = "Activation Code For Personnel Logistic Accounting System";
			$activationUrl = secure_url('/verify-account/'.$auth_code);
			$body = 'Please Click On This link '.$activationUrl.' to activate your account.';
			try {
				Mail::raw('Hi, welcome user! '.$body.'',function ($message) use($subject, $email) {
					$message->from(config('mail.from.address'), config('mail.from.name'));
					$message->to($email)->subject($subject);
				});
				echo 'Mail is sent';
			} catch (\Throwable $e) {
				echo 'Mail could not be sent';
			}
		}

		public function systemSettings(Request $request) {
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}

			$site_title = 'Personnel Logistic Accounting System';
			try {
				$value = DB::table('site_settings')->where('setting_key', '=', 'site_title')->value('setting_value');
				if(is_string($value) && trim($value) !== '') {
					$site_title = $value;
				}
			} catch (\Throwable $e) {
			}

			$uniforms = [];
			try {
				$uniforms = DB::table('uniforms')
					->select('id', 'uniform_type', 'uniform_name', 'uniform_photo')
					->orderBy('uniform_type')
					->orderBy('uniform_name')
					->get();
			} catch (\Throwable $e) {
				$uniforms = [];
			}

			$uniformItemsByUniform = [];
			try {
				$uniformItemsQuery = DB::table('uniform_clothes')
					->select('id', 'uniforms_id', 'clothes_type', 'accessory');

				if ($this->hasUniformClothesPhotoColumn()) {
					$uniformItemsQuery->addSelect('clothes_photo');
				} else {
					$uniformItemsQuery->addSelect(DB::raw('NULL as clothes_photo'));
				}

				$uniformItems = $uniformItemsQuery
					->orderBy('uniforms_id')
					->orderBy('accessory')
					->orderBy('clothes_type')
					->get();

				foreach ($uniformItems as $uniformItem) {
					$uniformId = (int) $uniformItem->uniforms_id;
					if (!isset($uniformItemsByUniform[$uniformId])) {
						$uniformItemsByUniform[$uniformId] = [];
					}
					$uniformItemsByUniform[$uniformId][] = $uniformItem;
				}
			} catch (\Throwable $e) {
				$uniformItemsByUniform = [];
			}

			// --- Skala Kelayakan Pakaian -------------------------------------
			// Air Force ranks only, grouped Officer / Non-Officer via
			// pangkats.officer_recruit (1 = officer, 2 = non-officer).
			$ranksByCategory = ['officer' => [], 'non_officer' => []];
			try {
				$ranks = DB::table('pangkats')
					->select('id', 'value', 'officer_recruit', 'pangkats_order')
					->where('piliih_angkatan_id', '=', (string) $this->airForceAngkatanId())
					->orderBy('pangkats_order')
					->get();

				foreach ($ranks as $rank) {
					$key = (int) $rank->officer_recruit === 1 ? 'officer' : 'non_officer';
					$ranksByCategory[$key][] = $rank;
				}
			} catch (\Throwable $e) {
				$ranksByCategory = ['officer' => [], 'non_officer' => []];
			}

			$selectedRankId = (int) $request->query('pangkat_id');
			if (!$selectedRankId) {
				$firstGroup = count($ranksByCategory['officer']) ? 'officer' : 'non_officer';
				if (count($ranksByCategory[$firstGroup])) {
					$selectedRankId = (int) $ranksByCategory[$firstGroup][0]->id;
				}
			}

			$scaleService = app(\App\Services\UniformScaleService::class);

			return view('admin/system_settings', [
				'site_title' => $site_title,
				'uniforms' => $uniforms,
				'uniformItemsByUniform' => $uniformItemsByUniform,
				'ranksByCategory' => $ranksByCategory,
				'selectedRankId' => $selectedRankId,
				'rankScales' => $scaleService->scalesForRank($selectedRankId ?: null),
				'scaleStorageReady' => $scaleService->tableExists(),
				'hiddenUniformIds' => $scaleService->hiddenUniformsForRank($selectedRankId ?: null),
				'hiddenStorageReady' => $scaleService->hiddenTableExists(),
			]);
		}

		/**
		 * id of the Air Force branch in piliih_angkatans.
		 *
		 * Resolved by name rather than hardcoded, because the stored label
		 * carries a double space ("AIR  FORCE") and the ids are data, not
		 * constants. Falls back to 1, which is the current value.
		 */
		private function airForceAngkatanId(): int {
			static $airForceId = null;

			if ($airForceId !== null) {
				return $airForceId;
			}

			try {
				$match = DB::table('piliih_angkatans')
					->whereRaw("REPLACE(UPPER(value), ' ', '') = ?", ['AIRFORCE'])
					->value('id');

				$airForceId = $match ? (int) $match : 1;
			} catch (\Throwable $e) {
				$airForceId = 1;
			}

			return $airForceId;
		}

		/**
		 * Saves the entitlement scale for one rank.
		 *
		 * A blank field means "not configured" and removes any stored row, so the
		 * item falls back to being orderable without a cap. An explicit 0 stores a
		 * row that blocks the item. This keeps the three states distinguishable --
		 * see App\Services\UniformScaleService.
		 */
		private function saveUniformScales(Request $request) {
			$pangkatId = (int) $request->input('pangkat_id');
			$redirect = route('admin.system-settings') . '?tab=scale' . ($pangkatId > 0 ? '&pangkat_id=' . $pangkatId : '');

			if ($pangkatId <= 0) {
				\Session::flash('message', 'Sila pilih pangkat terlebih dahulu.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->to($redirect);
			}

			// The form only offers Air Force ranks; re-check server-side so a
			// crafted post cannot set a scale against another branch.
			$isAirForceRank = false;
			try {
				$isAirForceRank = DB::table('pangkats')
					->where('id', '=', $pangkatId)
					->where('piliih_angkatan_id', '=', (string) $this->airForceAngkatanId())
					->exists();
			} catch (\Throwable $e) {
				$isAirForceRank = false;
			}

			if (!$isAirForceRank) {
				\Session::flash('message', 'Pangkat tidak sah. Hanya pangkat AIR FORCE dibenarkan.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->to(route('admin.system-settings') . '?tab=scale');
			}

			$scaleService = app(\App\Services\UniformScaleService::class);
			if (!$scaleService->tableExists()) {
				\Session::flash('message', 'Jadual skala belum wujud. Sila jalankan "php artisan migrate".');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->to($redirect);
			}

			$submitted = $request->input('scales');
			$submitted = is_array($submitted) ? $submitted : [];

			// Only touch items that were actually on the submitted form, so saving
			// one rank never disturbs another.
			$validIds = [];
			try {
				$validIds = DB::table('uniform_clothes')->pluck('id')->map(fn ($id) => (int) $id)->toArray();
			} catch (\Throwable $e) {
				$validIds = [];
			}
			$validIds = array_flip($validIds);

			$saved = 0;
			$cleared = 0;

			try {
				DB::transaction(function () use ($submitted, $validIds, $pangkatId, &$saved, &$cleared) {
					foreach ($submitted as $clothesId => $rawValue) {
						$clothesId = (int) $clothesId;
						if (!isset($validIds[$clothesId])) {
							continue;
						}

						$value = is_string($rawValue) ? trim($rawValue) : $rawValue;

						if ($value === '' || $value === null) {
							DB::table('uniform_scales')
								->where('pangkat_id', '=', $pangkatId)
								->where('uniform_clothes_id', '=', $clothesId)
								->delete();
							$cleared++;
							continue;
						}

						if (!is_numeric($value)) {
							continue;
						}

						$quantity = (int) $value;
						if ($quantity < 0) {
							$quantity = 0;
						}
						if ($quantity > 999) {
							$quantity = 999;
						}

						DB::table('uniform_scales')->updateOrInsert(
							['pangkat_id' => $pangkatId, 'uniform_clothes_id' => $clothesId],
							['max_quantity' => $quantity, 'updated_at' => now(), 'created_at' => now()]
						);
						$saved++;
					}
				});
			} catch (\Throwable $e) {
				\Session::flash('message', 'Gagal simpan skala kelayakan.');
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->to($redirect);
			}

			\Session::flash('message', 'Skala kelayakan disimpan. ' . $saved . ' item ditetapkan, ' . $cleared . ' item dikosongkan.');
			\Session::flash('alert-class', 'alert-success');
			return redirect()->to($redirect);
		}

		/**
		 * AJAX: toggle whether a uniform is hidden from the order cart for a rank.
		 * Presence of a uniform_rank_hidden row = hidden. Auto-saved from the
		 * scale tab's tickboxes, so there is no form submit.
		 */
		public function toggleUniformVisibility(Request $request) {
			if ($request->session()->get('admin_id') == '') {
				return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
			}

			$pangkatId = (int) $request->input('pangkat_id');
			$uniformsId = (int) $request->input('uniforms_id');
			$hidden = filter_var($request->input('hidden'), FILTER_VALIDATE_BOOLEAN);

			if ($pangkatId <= 0 || $uniformsId <= 0) {
				return response()->json(['ok' => false, 'message' => 'Permintaan tidak sah.'], 422);
			}

			$scaleService = app(\App\Services\UniformScaleService::class);
			if (!$scaleService->hiddenTableExists()) {
				return response()->json(['ok' => false, 'message' => 'Jadual belum wujud. Jalankan "php artisan migrate".'], 422);
			}

			// Only AIR FORCE ranks are configurable here (see saveUniformScales).
			try {
				$validRank = DB::table('pangkats')
					->where('id', '=', $pangkatId)
					->where('piliih_angkatan_id', '=', (string) $this->airForceAngkatanId())
					->exists();
				$validUniform = DB::table('uniforms')->where('id', '=', $uniformsId)->exists();
			} catch (\Throwable $e) {
				$validRank = false;
				$validUniform = false;
			}

			if (!$validRank || !$validUniform) {
				return response()->json(['ok' => false, 'message' => 'Pangkat atau uniform tidak sah.'], 422);
			}

			try {
				if ($hidden) {
					DB::table('uniform_rank_hidden')->updateOrInsert(
						['pangkat_id' => $pangkatId, 'uniforms_id' => $uniformsId],
						['updated_at' => now(), 'created_at' => now()]
					);
				} else {
					DB::table('uniform_rank_hidden')
						->where('pangkat_id', '=', $pangkatId)
						->where('uniforms_id', '=', $uniformsId)
						->delete();
				}
			} catch (\Throwable $e) {
				return response()->json(['ok' => false, 'message' => 'Gagal menyimpan.'], 500);
			}

			return response()->json(['ok' => true, 'hidden' => $hidden]);
		}

		/**
		 * AJAX: save one item's entitlement scale for a rank. Same blank/0/n
		 * semantics as saveUniformScales, one item at a time so each number
		 * field can auto-save on change without a form submit.
		 */
		public function saveUniformScaleItem(Request $request) {
			if ($request->session()->get('admin_id') == '') {
				return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
			}

			$pangkatId = (int) $request->input('pangkat_id');
			$clothesId = (int) $request->input('uniform_clothes_id');
			$rawValue = $request->input('value');

			if ($pangkatId <= 0 || $clothesId <= 0) {
				return response()->json(['ok' => false, 'message' => 'Permintaan tidak sah.'], 422);
			}

			$scaleService = app(\App\Services\UniformScaleService::class);
			if (!$scaleService->tableExists()) {
				return response()->json(['ok' => false, 'message' => 'Jadual skala belum wujud. Jalankan "php artisan migrate".'], 422);
			}

			try {
				$validRank = DB::table('pangkats')
					->where('id', '=', $pangkatId)
					->where('piliih_angkatan_id', '=', (string) $this->airForceAngkatanId())
					->exists();
				$validItem = DB::table('uniform_clothes')->where('id', '=', $clothesId)->exists();
			} catch (\Throwable $e) {
				$validRank = false;
				$validItem = false;
			}

			if (!$validRank || !$validItem) {
				return response()->json(['ok' => false, 'message' => 'Pangkat atau item tidak sah.'], 422);
			}

			$value = is_string($rawValue) ? trim($rawValue) : $rawValue;

			try {
				if ($value === '' || $value === null) {
					DB::table('uniform_scales')
						->where('pangkat_id', '=', $pangkatId)
						->where('uniform_clothes_id', '=', $clothesId)
						->delete();

					return response()->json(['ok' => true, 'value' => null, 'state' => 'unset']);
				}

				if (!is_numeric($value)) {
					return response()->json(['ok' => false, 'message' => 'Nilai mesti nombor.'], 422);
				}

				$quantity = (int) $value;
				if ($quantity < 0) {
					$quantity = 0;
				}
				if ($quantity > 999) {
					$quantity = 999;
				}

				DB::table('uniform_scales')->updateOrInsert(
					['pangkat_id' => $pangkatId, 'uniform_clothes_id' => $clothesId],
					['max_quantity' => $quantity, 'updated_at' => now(), 'created_at' => now()]
				);
			} catch (\Throwable $e) {
				return response()->json(['ok' => false, 'message' => 'Gagal menyimpan.'], 500);
			}

			return response()->json([
				'ok' => true,
				'value' => $quantity,
				'state' => $quantity === 0 ? 'blocked' : 'set',
			]);
		}

		public function saveSystemSettings(Request $request) {
			if($request->session()->get('admin_id') == '') {
				return redirect()->route('site-admin.login');
			}

			// The scale tab is independent of the title/logo form, so it returns
			// before that form's own validation runs.
			if ($request->query('tab') === 'scale') {
				return $this->saveUniformScales($request);
			}

			$redirectTab = $request->query('tab') === 'uniform' ? 'uniform' : 'system';
			$selectedUniformId = (int) $request->input('selected_uniform_id');
			$redirectUniformSuffix = $selectedUniformId > 0 ? '&uniform_id=' . $selectedUniformId : '';

			$validator = Validator::make(
				['site_title' => $request->input('site_title')],
				['site_title' => 'required|string|max:255']
			);
			if($validator->fails()) {
				return redirect()->to(route('admin.system-settings') . '?tab=' . $redirectTab . $redirectUniformSuffix)->withErrors($validator)->withInput();
			}

			$site_title = trim((string) $request->input('site_title'));
			$uniformFields = $request->input('uniforms');

			try {
				$exists = DB::table('site_settings')->where('setting_key', '=', 'site_title')->count();
				if($exists > 0) {
					DB::table('site_settings')->where('setting_key', '=', 'site_title')->update(['setting_value' => $site_title]);
				}
				else {
					DB::table('site_settings')->insert(['setting_key' => 'site_title', 'setting_value' => $site_title]);
				}
			} catch (\Throwable $e) {
				\Session::flash('message', 'Gagal simpan tajuk sistem.'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->to(route('admin.system-settings') . '?tab=' . $redirectTab . $redirectUniformSuffix);
			}

			$uploadsPath = public_path('uploads');
			if(!is_dir($uploadsPath) || !is_writable($uploadsPath)) {
				\Session::flash('message', 'Folder public/uploads tidak boleh ditulis. Sila semak permission IIS (Modify).'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->to(route('admin.system-settings') . '?tab=' . $redirectTab . $redirectUniformSuffix);
			}

			$logoCandidate = null;
			try {
				$logoCandidate = $request->files->get('logo');
			} catch (\Throwable $e) {
				$logoCandidate = null;
			}

			$allowedExt = ['jpg', 'jpeg', 'png'];
			$maxBytes = 5 * 1024 * 1024;
			$uploaded = false;

			if($logoCandidate instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
				if(!$logoCandidate->isValid()) {
					$err = (int) $logoCandidate->getError();
					$msg = 'Logo tidak berjaya dimuat naik.';
					if($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
						$msg = 'Saiz fail logo terlalu besar. Sila kecilkan saiz fail atau naikkan had upload.';
					}
					else if($err === UPLOAD_ERR_PARTIAL) {
						$msg = 'Fail logo hanya dimuat naik sebahagian. Cuba sekali lagi.';
					}
					else if($err === UPLOAD_ERR_NO_TMP_DIR) {
						$msg = 'Tiada folder sementara (tmp) untuk upload.';
					}
					else if($err === UPLOAD_ERR_CANT_WRITE) {
						$msg = 'Server tidak boleh menulis fail upload. Sila semak permission.';
					}
					else if($err === UPLOAD_ERR_EXTENSION) {
						$msg = 'Upload dihentikan oleh extension PHP.';
					}
					\Session::flash('message', $msg); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}

				$ext = strtolower((string) $logoCandidate->getClientOriginalExtension());
				if($ext === '') {
					$ext = 'png';
				}
				if(!in_array($ext, $allowedExt)) {
					\Session::flash('message', 'Format logo tidak disokong. Sila guna PNG atau JPG.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}
				$size = (int) $logoCandidate->getSize();
				if($size > $maxBytes) {
					\Session::flash('message', 'Saiz fail logo terlalu besar. Maksimum 5MB.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}

				$filename = 'site_logo.' . $ext;
				try {
					$logoCandidate->move($uploadsPath, $filename);
					$relativePath = 'uploads/' . $filename;
					$version = (string) time();
					$uploaded = true;
				} catch (\Throwable $e) {
					\Session::flash('message', 'Gagal simpan fail logo.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}
			}
			else if(isset($_FILES['logo']) && is_array($_FILES['logo']) && isset($_FILES['logo']['tmp_name']) && (string) $_FILES['logo']['tmp_name'] !== '') {
				$err = isset($_FILES['logo']['error']) ? (int) $_FILES['logo']['error'] : 0;
				if($err !== UPLOAD_ERR_OK) {
					$msg = 'Logo tidak berjaya dimuat naik.';
					if($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
						$msg = 'Saiz fail logo terlalu besar. Sila kecilkan saiz fail atau naikkan had upload.';
					}
					else if($err === UPLOAD_ERR_PARTIAL) {
						$msg = 'Fail logo hanya dimuat naik sebahagian. Cuba sekali lagi.';
					}
					else if($err === UPLOAD_ERR_NO_TMP_DIR) {
						$msg = 'Tiada folder sementara (tmp) untuk upload.';
					}
					else if($err === UPLOAD_ERR_CANT_WRITE) {
						$msg = 'Server tidak boleh menulis fail upload. Sila semak permission.';
					}
					else if($err === UPLOAD_ERR_EXTENSION) {
						$msg = 'Upload dihentikan oleh extension PHP.';
					}
					\Session::flash('message', $msg); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}

				$tmp = (string) $_FILES['logo']['tmp_name'];
				$name = (string) $_FILES['logo']['name'];
				$size = isset($_FILES['logo']['size']) ? (int) $_FILES['logo']['size'] : 0;
				if($size > $maxBytes) {
					\Session::flash('message', 'Saiz fail logo terlalu besar. Maksimum 5MB.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}

				$ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
				if(!in_array($ext, $allowedExt)) {
					\Session::flash('message', 'Format logo tidak disokong. Sila guna PNG atau JPG.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}

				if(!is_uploaded_file($tmp)) {
					\Session::flash('message', 'Fail logo tidak sah untuk upload.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}

				$filename = 'site_logo.' . $ext;
				$dest = $uploadsPath . DIRECTORY_SEPARATOR . $filename;
				if(file_exists($dest)) {
					@unlink($dest);
				}
				if(!move_uploaded_file($tmp, $dest)) {
					\Session::flash('message', 'Gagal simpan fail logo.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}

				$relativePath = 'uploads/' . $filename;
				$version = (string) time();
				$uploaded = true;
			}

			if($uploaded) {
				try {
					$existsLogo = DB::table('site_settings')->where('setting_key', '=', 'site_logo')->count();
					if($existsLogo > 0) {
						DB::table('site_settings')->where('setting_key', '=', 'site_logo')->update(['setting_value' => $relativePath]);
					}
					else {
						DB::table('site_settings')->insert(['setting_key' => 'site_logo', 'setting_value' => $relativePath]);
					}

					$existsLogoV = DB::table('site_settings')->where('setting_key', '=', 'site_logo_version')->count();
					if($existsLogoV > 0) {
						DB::table('site_settings')->where('setting_key', '=', 'site_logo_version')->update(['setting_value' => $version]);
					}
					else {
						DB::table('site_settings')->insert(['setting_key' => 'site_logo_version', 'setting_value' => $version]);
					}
				} catch (\Throwable $e) {
					\Session::flash('message', 'Logo berjaya dimuat naik tetapi gagal dikemaskini dalam sistem.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=system');
				}
			}

			if (is_array($uniformFields) && !empty($uniformFields)) {
				foreach ($uniformFields as $uniformId => $uniformField) {
					$uniformId = (int) $uniformId;
					if ($uniformId <= 0 || !is_array($uniformField)) {
						continue;
					}

					$uniformType = isset($uniformField['uniform_type']) ? strtoupper(trim((string) $uniformField['uniform_type'])) : '';
					$uniformName = isset($uniformField['uniform_name']) ? trim((string) $uniformField['uniform_name']) : '';

					if ($uniformType === '') {
						\Session::flash('message', 'Jenis uniform tidak boleh kosong.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix)->withInput();
					}

					try {
						DB::table('uniforms')->where('id', '=', $uniformId)->update([
							'uniform_type' => $uniformType,
							'uniform_name' => $uniformName !== '' ? $uniformName : null,
						]);
					} catch (\Throwable $e) {
						\Session::flash('message', 'Maklumat uniform gagal dikemaskini.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix)->withInput();
					}
				}
			}

			$uniformCandidates = null;
			try {
				$uniformCandidates = $request->files->get('uniform_photos');
			} catch (\Throwable $e) {
				$uniformCandidates = null;
			}

			if (is_array($uniformCandidates) && !empty($uniformCandidates)) {
				foreach ($uniformCandidates as $uniformId => $uniformFile) {
					$uniformId = (int) $uniformId;
					if ($uniformId <= 0) {
						continue;
					}
					if (!($uniformFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
						continue;
					}
					if (!$uniformFile->isValid()) {
						\Session::flash('message', 'Gambar uniform tidak berjaya dimuat naik.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}

					$ext = strtolower((string) $uniformFile->getClientOriginalExtension());
					if ($ext === '') {
						$ext = 'jpg';
					}
					if (!in_array($ext, $allowedExt)) {
						\Session::flash('message', 'Format gambar uniform tidak disokong. Sila guna PNG atau JPG.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}
					$size = (int) $uniformFile->getSize();
					if ($size > $maxBytes) {
						\Session::flash('message', 'Saiz fail gambar uniform terlalu besar. Maksimum 5MB.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}

					$filename = 'uniform_' . $uniformId . '_' . time() . '.' . $ext;
					try {
						$uniformFile->move($uploadsPath, $filename);
					} catch (\Throwable $e) {
						\Session::flash('message', 'Gagal simpan fail gambar uniform.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}

					try {
						DB::table('uniforms')->where('id', '=', $uniformId)->update(['uniform_photo' => 'uploads/' . $filename]);
					} catch (\Throwable $e) {
						\Session::flash('message', 'Gambar uniform berjaya dimuat naik tetapi gagal dikemaskini dalam sistem.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}
				}
			}

			$uniformClothesCandidates = null;
			try {
				$uniformClothesCandidates = $request->files->get('uniform_clothes_photos');
			} catch (\Throwable $e) {
				$uniformClothesCandidates = null;
			}

			if (is_array($uniformClothesCandidates) && !empty($uniformClothesCandidates)) {
				if (!$this->hasUniformClothesPhotoColumn()) {
					\Session::flash('message', 'Ruangan gambar clothes/accessories belum wujud. Sila jalankan migration terlebih dahulu.'); 
					\Session::flash('alert-class', 'alert-danger');
					return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
				}

				foreach ($uniformClothesCandidates as $uniformClothesId => $uniformClothesFile) {
					$uniformClothesId = (int) $uniformClothesId;
					if ($uniformClothesId <= 0) {
						continue;
					}
					if (!($uniformClothesFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile)) {
						continue;
					}
					if (!$uniformClothesFile->isValid()) {
						\Session::flash('message', 'Gambar clothes/accessories tidak berjaya dimuat naik.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}

					$ext = strtolower((string) $uniformClothesFile->getClientOriginalExtension());
					if ($ext === '') {
						$ext = 'jpg';
					}
					if (!in_array($ext, $allowedExt)) {
						\Session::flash('message', 'Format gambar clothes/accessories tidak disokong. Sila guna PNG atau JPG.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}
					$size = (int) $uniformClothesFile->getSize();
					if ($size > $maxBytes) {
						\Session::flash('message', 'Saiz fail gambar clothes/accessories terlalu besar. Maksimum 5MB.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}

					$filename = 'uniform_clothes_' . $uniformClothesId . '_' . time() . '.' . $ext;
					try {
						$uniformClothesFile->move($uploadsPath, $filename);
					} catch (\Throwable $e) {
						\Session::flash('message', 'Gagal simpan fail gambar clothes/accessories.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}

					try {
						DB::table('uniform_clothes')->where('id', '=', $uniformClothesId)->update(['clothes_photo' => 'uploads/' . $filename]);
					} catch (\Throwable $e) {
						\Session::flash('message', 'Gambar clothes/accessories berjaya dimuat naik tetapi gagal dikemaskini dalam sistem.'); 
						\Session::flash('alert-class', 'alert-danger');
						return redirect()->to(route('admin.system-settings') . '?tab=uniform' . $redirectUniformSuffix);
					}
				}
			}

			\Session::flash('message', 'Tetapan sistem berjaya dikemaskini.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->to(route('admin.system-settings') . '?tab=' . $redirectTab . $redirectUniformSuffix);
		}
	}
