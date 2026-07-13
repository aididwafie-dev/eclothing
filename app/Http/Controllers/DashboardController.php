<?php
	namespace App\Http\Controllers;
	
	use App\Models\Personal_detail;
	use App\Models\Order;
	use App\Models\Ordered_clothe;
	use DB;
	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\Redirect;
	use Illuminate\Support\Facades\Route;
	use Illuminate\Support\Facades\Schema;
	use App\Http\Requests;
	use Illuminate\Mail\Mailer;
	use Mail;
	use Session;
	use App\Services\OrderStatusService;
	use App\Services\AssignedUniformService;
	use App\Services\UniformCartRules;
	use App\Services\OrderCheckoutService;

	class DashboardController extends Controller {
		
		public function index(Request $request) {
			
			if($request->session()->get('user_id') == '')
			{
				return redirect()->route('user.login');
			}

			$personal_detail = DB::table('personal_details')->where('user_id', '=', $request->session()->get('user_id'))->first();
			$user_details = DB::table('gen_users')->where('id', '=', $request->session()->get('user_id'))->first();
			$service_id = $user_details ? $user_details->s_id : ($personal_detail ? $personal_detail->s_id : '');
			$data=['personal_data'=>[
			 	'service_id' => $service_id,
			 	'dropdown_data' => $this->getDropdownValues(),
			 	'personal_detail' => $personal_detail,
			]];
			$userDetails = $this->checkUserDetails($request); //this data is for the sidebar portion.
			
			return view('personal_details', array("data"=>$data,"userDetails"=>$userDetails));
		}

		public function checkUserDetails(Request $request) {

			$user_id = $request->session()->get('user_id');
			$userDetails = DB::table('gen_users')->where('id', '=', $user_id)->first();
			return $userDetails;
		}

		public function getDropdownValues() {

			$piliih_angkatans = DB::table('piliih_angkatans')->get();
			$ketukangans_officer = DB::table('ketukangans')->where('officer_recruit', '=', 1)->get();
			
			$ketukangans_recruit = DB::table('ketukangans')->where('officer_recruit', '=', 2)->get();
			
			$ketukangans_both = DB::table('ketukangans')->where('officer_recruit', '=', 3)->get();

			$units = DB::table('units')->get();
			$jantinas = DB::table('jantinas')->get();
			$status_penggunaans = DB::table('status_penggunaans')->get();

			$result = array(
				'piliih_angkatans'=>$piliih_angkatans,				
				'ketukangans_officer'=>$ketukangans_officer,
				'ketukangans_recruit'=>$ketukangans_recruit,
				'ketukangans_both'=>$ketukangans_both,
				'units'=>$units,
				'jantinas'=>$jantinas,
				'status_penggunaans'=>$status_penggunaans,
			);
			
			return $result;
		}
		
		public function ajaxLoadRankValues(Request $request) {
			if (!$request->session()) {
				die(json_encode(["refresh"=>1]));
			}
			
			$service_id = $request->input('serviceId');
			$tred_type = $request->input('tredType');
			$personal_detail = DB::table('personal_details')->where('user_id', '=', $request->session()->get('user_id'))->first();
			$pangkats = DB::table('pangkats')->where('officer_recruit', '=', $tred_type)->where('piliih_angkatan_id', '=', $service_id)->get();
			return view('rank_dropdown_values',array('pangkats'=>$pangkats,'personal_detail'=>$personal_detail)); //loading the same page for both Admin and User
		}

		public function savePersonalDetails(Request $request) {

			$address_array = [
				0 => $request->address1,
				1 => $request->address2,
				2 => $request->address3,
				3 => $request->address4,
			];
			$address = implode('|', $address_array);
			if($request->ketukangans_type == 1) {
				$ketukangans_type = 1;
				$ketukangan = $request->ketukangans_officer;
			}
			else if($request->ketukangans_type == 2) {
				$ketukangans_type = 2;
				$ketukangan = $request->ketukangans_recruit;
			} else {
								$ketukangans_type = 3;
			}
			$detail = DB::table('personal_details')->where('user_id', '=', $request->session()->get('user_id'))->first();
			if(!empty($detail))
			{
				$personal_detail = Personal_detail::find($detail->id);
				\Session::flash('message', 'Your Personal details are successfully updated.'); 
				\Session::flash('alert-class', 'alert-success');
			}
			else
			{
				$personal_detail = new Personal_detail;
				\Session::flash('message', 'Your Personal details are successfully saved. Please proceed with ordering your uniform.'); 
				\Session::flash('alert-class', 'alert-success');
			}
			
			$personal_detail->user_id = $request->session()->get('user_id');
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

			$time = date("Y-m-d h:i:s");
			DB::table('gen_users')->where('id', '=', $request->session()->get('user_id'))->update(['profile_status' => 1]);

			return redirect()->route('user.uniform');
		}

		public function restorePersonalDetails() {

			\Session::flash('message', 'Details restored'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('user.personal');
		}

		public function cancelSave() {

			\Session::flash('message', 'Process canceled'); 
			\Session::flash('alert-class', 'alert-danger');
			return redirect()->route('user.personal');
		}

		public function getUniformInfo(Request $request) {

			$user_id = $request->session()->get('user_id');
			$personalDetails = DB::table('personal_details')->where('user_id', '=', $user_id)->first();

			return ['uniforms' => app(AssignedUniformService::class)->forPersonalDetail($personalDetails)];
		}

		public function getAccessoriesInfo(Request $request) {

			$user_id = $request->session()->get('user_id');
			$personalDetails = DB::table('personal_details')->where('user_id', '=', $user_id)->first();

			return ['uniforms' => app(AssignedUniformService::class)->forPersonalDetail($personalDetails)];
		}

		public function userUniformSelection(Request $request) {

			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}
			$userDetails = $this->checkUserDetails($request); //this data is for the sidebar portion.
			if(!$userDetails || $userDetails->profile_status != 1)
			{
				\Session::flash('message', 'Please complete your personal details first, before ordering uniform.'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('user.personal'); //if user enable the uniform button by inspecting the plage and try to place a order, this will redirect him back.
			}
			$data = $this->getUniformInfo($request);
			
			return view('uniform_selection',array("data"=>$data,"userDetails"=>$userDetails));
		}

		public function userAccessoriesSelection(Request $request) {

			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}
			$userDetails = $this->checkUserDetails($request); //this data is for the sidebar portion.
			if(!$userDetails || $userDetails->profile_status != 1)
			{
				\Session::flash('message', 'Please complete your personal details first, before ordering accessories.'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('user.personal'); //if user enable the uniform button by inspecting the plage and try to place a order, this will redirect him back.
			}
			$data = $this->getUniformInfo($request);
			
			return view('uniform_selection',array("data"=>$data,"userDetails"=>$userDetails));
		}

		public function getClothesDataForForm($uniform_id) {

			$uniform_clothes = DB::table('uniform_clothes')->where('uniforms_id', '=', $uniform_id)->orderBy("accessory", "asc")->get();

			return $uniform_clothes;
		}

		public function loadUniformData(Request $request) {

			$uniform_id = $request->input('uniform_id');
		
			$uniform_clothes = $this->getClothesDataForForm($uniform_id);
			
			$userDetails = DB::table('gen_users')->leftJoin("personal_details", "gen_users.id", "=", "personal_details.user_id")->where('gen_users.id', '=', $request->session()->get('user_id'))->first();
			
			foreach ($uniform_clothes as $id => $uniform_cloth) {
					if ($uniform_cloth->jantina && $uniform_cloth->jantina != $userDetails->jantina) {
						unset($uniform_clothes[$id]);
					} else if ($uniform_cloth->pangkat && $uniform_cloth->pangkat != $userDetails->pangkat) {
						unset($uniform_clothes[$id]);
					} else if ($uniform_cloth->ketukangan && $uniform_cloth->ketukangan != $userDetails->ketukangan) {
						unset($uniform_clothes[$id]);
					} else if ($uniform_cloth->religion && $uniform_cloth->religion != $userDetails->religion) {
						unset($uniform_clothes[$id]);
					}
			}
			
			$sizes = DB::table('sizes')->get();
			$user_id = session()->get('user_id');

			$orders_r = DB::table('ordered_clothes')->leftJoin("orders", "orders.id", "=", "ordered_clothes.order_id")->where('orders.uniforms_id', '=', $uniform_id)->where('deleted', '=', 0)->where('orders.user_id', '=', $user_id)->orderBy("orders.created_at", "desc")->get();
			
			if ($orders_r) {
			foreach ($uniform_clothes as $id => $uniform_cloth) {
				foreach ($orders_r as $order) {
					if ($uniform_cloth->clothes_slug == $order->clothes_slug) {
						$uniform_clothes[$id]->ordered_size = $order->size;
					}
				}
			}
			}
										$uniform = DB::table('uniforms')->where('id', '=', $uniform_id)->first();

			$cart = $this->getUniformCart($request);
			$cartItems = isset($cart[$uniform_id]) && is_array($cart[$uniform_id]) ? $cart[$uniform_id] : [];
			$cartCount = 0;
			foreach ($cart as $cartGroup) {
				$cartCount += is_array($cartGroup) ? count($cartGroup) : 0;
			}

			foreach ($uniform_clothes as $id => $uniform_cloth) {
				$uniform_clothes[$id]->in_cart = isset($cartItems[$uniform_cloth->clothes_slug]);
				$uniform_clothes[$id]->cart_value = $uniform_clothes[$id]->in_cart ? $cartItems[$uniform_cloth->clothes_slug]['size'] : null;
			}
			
			return view('uniform_selection_form',array(
				'uniform_clothes'=>$uniform_clothes,
				'sizes'=>$sizes,
				'uniform'=>$uniform,
				'cartItems' => $cartItems,
				'cartCount' => $cartCount,
			));
		}

		private function getUniformCart(Request $request) {
			$cart = $request->session()->get('uniform_cart');
			return is_array($cart) ? $cart : [];
		}

		private function setUniformCart(Request $request, $cart) {
			$request->session()->put('uniform_cart', $cart);
		}

		private function orderStatus(): OrderStatusService {
			return app(OrderStatusService::class);
		}

		public function addUniformCartItem(Request $request) {
			if($request->session()->get('user_id') == '') {
				return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
			}

			$uniforms_id = (int) $request->input('uniforms_id');
			$clothes_slug = (string) $request->input('clothes_slug');
			$size = $request->input('size');

			if (!$uniforms_id || !$clothes_slug) {
				return response()->json(['ok' => false, 'message' => 'Invalid request'], 422);
			}

			$uniform = DB::table('uniforms')->where('id', '=', $uniforms_id)->first();
			$cloth = DB::table('uniform_clothes')
				->where('uniforms_id', '=', $uniforms_id)
				->where('clothes_slug', '=', $clothes_slug)
				->first();

			if (!$uniform || !$cloth) {
				return response()->json(['ok' => false, 'message' => 'Item not found'], 404);
			}

			$cart = $this->getUniformCart($request);
			if (!isset($cart[$uniforms_id]) || !is_array($cart[$uniforms_id])) {
				$cart[$uniforms_id] = [];
			}

			$normalizedSize = UniformCartRules::normalizeSize($size);

			if (UniformCartRules::isEmptySize($normalizedSize)) {
				unset($cart[$uniforms_id][$clothes_slug]);
				$this->setUniformCart($request, $cart);
				return response()->json(['ok' => true]);
			}

			$displayName = $uniform->uniform_name ? $uniform->uniform_name : $uniform->uniform_type;
			$cart[$uniforms_id][$clothes_slug] = [
				'uniforms_id' => $uniforms_id,
				'clothes_slug' => $clothes_slug,
				'clothes_type' => $cloth->clothes_type,
				'uniform_name' => $displayName,
				'size' => $normalizedSize,
			];

			$this->setUniformCart($request, $cart);
			return response()->json(['ok' => true]);
		}

		public function removeUniformCartItem(Request $request) {
			if($request->session()->get('user_id') == '') {
				return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
			}

			$uniforms_id = (int) $request->input('uniforms_id');
			$clothes_slug = (string) $request->input('clothes_slug');

			$cart = $this->getUniformCart($request);
			if (isset($cart[$uniforms_id]) && isset($cart[$uniforms_id][$clothes_slug])) {
				unset($cart[$uniforms_id][$clothes_slug]);
				$this->setUniformCart($request, $cart);
			}

			return response()->json(['ok' => true]);
		}

		public function checkoutUniformCart(Request $request) {
			if($request->session()->get('user_id') == '') {
				return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
			}

			$user_id = (int) $request->session()->get('user_id');
			$cart = $this->getUniformCart($request);

			$hasItems = false;
			foreach ($cart as $group) {
				if (is_array($group) && count($group)) {
					$hasItems = true;
					break;
				}
			}
			if (!$hasItems) {
				return response()->json(['ok' => false, 'message' => 'Cart is empty'], 422);
			}

			app(OrderCheckoutService::class)->checkoutForUser($user_id, $cart);

			$request->session()->forget('uniform_cart');
			\Session::flash('message', 'Your Order is successfully saved.');
			\Session::flash('alert-class', 'alert-success');

			return response()->json(['ok' => true, 'redirect' => url('/user/ordered-uniform')]);
		}
		
		public function loadUniformPhotos(Request $request) {

			$uniform_id = $request->input('uniform_id');
			$uniform = DB::table('uniforms')->where('id', '=', $uniform_id)->first();

			if ($uniform && $uniform->uniform_type) {
				if ($uniform->uniform_photo) {
				$images = glob("uploads/" . $uniform->uniform_photo);
				} else {
				$images = glob("front_end/images/uniforms/" . $uniform->uniform_type . ".jpg");
				}
				if (count($images)) {
					$html = '';
					foreach ($images as $image) {
						$html .= '<img src="../' . $image . '" />';
					}
					return $html;
				}
			}
		}

		public function saveUniformDetailsInOrders(Request $request) {
			
			$user_id = session()->get('user_id');
			$uniforms_id = $request->input('uniforms_id');
			
			$user_order = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $user_id)->where('uniforms_id', '=', $uniforms_id)->first();
			if(!empty($user_order))
			{
				if ($this->orderStatus()->hasOrderLifecycleColumns()) {
					DB::table('orders')->where('id', '=', $user_order->id)->update([
						'status' => '1',
						'remarks' => null,
						'collection_date' => null,
						'updated_at' => date("Y-m-d H:i:s"),
					]);
				}
				foreach ($_POST as $key => $value) {
					if($key != 'submit' && $key != 'last_uniform' && $key != '_token' && $key != 'uniforms_id')
					{
					
						$user_ordered_cloth = DB::table('ordered_clothes')->where('order_id', '=', $user_order->id)->where('clothes_slug', '=', $key)->first();
						
						if ($user_ordered_cloth) {
						$ordered_clothes = Ordered_clothe::find($user_ordered_cloth->id);
						$ordered_clothes->size = $request->$key;
						if (is_array($ordered_clothes->size)) {
							$ordered_clothes->size = implode(",",$ordered_clothes->size);
						}
						$ordered_clothes->save();
						} else {
						$clothes_type = DB::table('uniform_clothes')->select('clothes_type')->where('clothes_slug', '=', $key)->first();
						$ordered_clothes = new Ordered_clothe;
						$ordered_clothes->order_id = $user_order->id;
						$ordered_clothes->clothes = $clothes_type->clothes_type;
						$ordered_clothes->clothes_slug = $key;
						$ordered_clothes->size = $request->$key;
						if (is_array($ordered_clothes->size)) {
							$ordered_clothes->size = implode(",",$ordered_clothes->size);
						}
							
							$ordered_clothes->save();
						}
					}
				}
				\Session::flash('message', 'Your Order is successfully updated.'); 
				\Session::flash('alert-class', 'alert-success');
			}
			else
			{
				$order = new Order;
				$order->user_id = $user_id;
				$order->uniforms_id = $uniforms_id;
				if ($this->orderStatus()->hasOrderLifecycleColumns()) {
					$order->status = '1';
					$order->remarks = null;
					$order->collection_date = null;
				}
				$order->save();

				$order_id = $order->id;

				foreach ($_POST as $key => $value) {
					if($key != 'submit' && $key != 'last_uniform' && $key != '_token' && $key != 'uniforms_id')
					{
						$clothes_type = DB::table('uniform_clothes')->select('clothes_type')->where('clothes_slug', '=', $key)->first();

						$ordered_clothes = new Ordered_clothe;
						$ordered_clothes->order_id = $order_id;
						$ordered_clothes->clothes = $clothes_type->clothes_type;
						$ordered_clothes->clothes_slug = $key;
						$ordered_clothes->size = $request->$key;
						$ordered_clothes->save();
					}
				}
				\Session::flash('message', 'Your Order is successfully saved.'); 
				\Session::flash('alert-class', 'alert-success');
			}
			$request->session()->put('uniform_ordered', $uniforms_id);
			
			if ($request->input('last_uniform') == "true") {
				return redirect()->route('user.ordered-uniform');
			}
			
			return redirect()->route('user.uniform');
		}

		public function getOrderedUniform(Request $request) {

			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}

			$userDetails = $this->checkUserDetails($request); //this data is for the sidebar portion.
			if(!$userDetails || $userDetails->profile_status != 1) {
				\Session::flash('message', 'Please complete your personal details first, before viewing your orders.'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('user.personal'); //if user enable the uniform button by inspecting the plage and try to place a order, this will redirect him back.
			}
			$checkIfOrdered = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $request->session()->get('user_id'))->first();
			if(!empty($checkIfOrdered)) {
				$userOrders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $request->session()->get('user_id'))->get();
				$i = 0;
				foreach ($userOrders as $userOrder) {
					$userOrder = $this->orderStatus()->normalizeOrderLifecycle($userOrder);
					$data[$i] = [
						'userOrders' => $userOrder,
						'orderedUniform' => DB::table('uniforms')->where('id', '=', $userOrder->uniforms_id)->first(),
						'orderCount' => DB::table('ordered_clothes')->where('order_id', '=', $userOrder->id)->count(),
						'orderDetails' => DB::table('ordered_clothes')->where('order_id', '=', $userOrder->id)->get(),
					];
					$i++;
				}
			}
			else {
				$data = 0;
			}
			
			return view('uniform_ordered_byUser',array("data"=>$data,"userDetails"=>$userDetails));
		}
		
		public function mailUserOrderDetails(Request $request) {
			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}
			$user_id = $request->session()->get('user_id');
			$user_email = DB::table('gen_users')->select('email')->where('id', '=', $user_id)->first();
			$userOrders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $user_id)->get();
			$i = 0;
			foreach ($userOrders as $userOrder) {
				$data[$i] = [
					'userOrders' => $userOrder,
					'orderedUniform' => DB::table('uniforms')->where('id', '=', $userOrder->uniforms_id)->first(),
					'orderDetails' => DB::table('ordered_clothes')->where('order_id', '=', $userOrder->id)->get(),
					'count' => DB::table('ordered_clothes')->where('order_id', '=', $userOrder->id)->count(),
				];
				$i++;
			}
			
			Mail::send('mail_user_orderDetails', array("data"=>$data), function($message) use($user_email){
				$message->subject('Order Summary from Personnel Logistic Accounting System');
				$message->from('email@example.com', 'Personnel Logistic Accounting System');
				$message->to($user_email->email);
			});
			echo 'mail has been sent';
		}
		
		public function deleteUserOrder(Request $request) {
			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}

			$user_id = $request->session()->get('user_id');

			$userOrders = DB::table('orders')->where('deleted', '=', 0)->where('user_id', '=', $user_id)->update(['deleted' => 1]);

			echo 'Order deleted';
		}

		/**
		 * Printable KEW.PS-8 (Borang Permohonan Stok) for a single order.
		 * Ownership is enforced via the where('user_id', ...) clause below -
		 * a user can only generate the form for their own orders, not by
		 * guessing/incrementing order ids.
		 */
		public function generateKewPs8Report(Request $request, $id) {
			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}

			$user_id = $request->session()->get('user_id');

			$order = DB::table('orders')->where('id', '=', $id)->where('user_id', '=', $user_id)->where('deleted', '=', 0)->first();
			if (!$order) {
				abort(404);
			}

			$personalDetail = DB::table('personal_details')->where('user_id', '=', $user_id)->first();

			$rankName = '';
			if ($personalDetail && !empty($personalDetail->pangkat)) {
				$rank = DB::table('pangkats')->where('id', '=', $personalDetail->pangkat)->first();
				$rankName = $rank->value ?? '';
			}

			$uniform = DB::table('uniforms')->where('id', '=', $order->uniforms_id)->first();
			$items = DB::table('ordered_clothes')->where('order_id', '=', $order->id)->get();

			return view('reports.kew_ps8', [
				'order' => $order,
				'uniform' => $uniform,
				'rankName' => $rankName,
				'applicantName' => $personalDetail->name ?? '',
				'applicantSId' => $personalDetail->s_id ?? '',
				'reportForms' => $this->chunkKewPs8Rows($items),
			]);
		}

		private function buildKewPs8Rows($items, int $minimumRows = 8, int $startIndex = 1): array {
			$rows = [];
			$index = $startIndex;

			foreach ($items as $item) {
				$rows[] = [
					'bil' => (string) $index,
					'perihal' => (string) ($item->clothes ?? ''),
					'dimohon' => '1',
					'catatan' => (string) ($item->size ?? ''),
				];
				$index++;
			}

			while (count($rows) < $minimumRows) {
				$rows[] = ['bil' => '', 'perihal' => '', 'dimohon' => '', 'catatan' => ''];
			}

			return $rows;
		}

		private function chunkKewPs8Rows($items, int $rowsPerForm = 8): array {
			$items = collect($items)->values()->all();

			if (empty($items)) {
				return [$this->buildKewPs8Rows([], $rowsPerForm, 1)];
			}

			$chunks = array_chunk($items, $rowsPerForm);
			$forms = [];
			$startIndex = 1;

			foreach ($chunks as $chunk) {
				$forms[] = $this->buildKewPs8Rows($chunk, $rowsPerForm, $startIndex);
				$startIndex += count($chunk);
			}

			return $forms;
		}
	}
?>
