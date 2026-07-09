<?php
	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\Redirect;
	use Illuminate\Support\Facades\Route;
	use App\Http\Requests;
	use App\Models\Gen_user;
	use DB;
	use Session;

	class UserChangesController extends Controller {

   		public function changePassword(Request $request) {
			
			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}
			$userDetails = $this->checkuserDetails($request); //this data is for the sidebar portion.
			return view('edit_password', array("userDetails" => $userDetails));
		}

		public function editPassword(Request $request) {
			
			$old_password = md5($request->input('old_password'));
			$new_password = md5($request->input('new_password'));
			$u_id = $request->session()->get('user_id');
			$time = date("Y-m-d H:i:s");
			$log = DB::table('gen_users')->where('id', '=', $u_id)->where('password', '=', $old_password)->count();
			if($log > 0) {
				DB::table('gen_users')->where('id', '=', $u_id)->update(['password' => $new_password]);
				\Session::flash('message', 'You have successfully updated your password.'); 
				\Session::flash('alert-class', 'alert-success');
				return redirect()->route('user.personal');
			}
			else {
				\Session::flash('message', 'Wrong password.'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('user.change-password');
			}
		}

		public function checkuserDetails(Request $request) {

			$user_id = $request->session()->get('user_id');
			$userDetails = DB::table('gen_users')->where('id', '=', $user_id)->first();
			return $userDetails;
		}

		public function changeEmail(Request $request) {

			if($request->session()->get('user_id') == '') {
				return redirect()->route('user.login');
			}
			$userDetails = $this->checkuserDetails($request); //this data is for the sidebar portion.
			return view('edit_email', array("userDetails" => $userDetails));
		}

		public function ifEmailAlreadyExists(Request $request) {

			$value = $request->input('value');
			
			$count = DB::table('gen_users')->where('email', '=', $value)->count();
			if($count == 0) {
				$result = "Click on confirm to save new Email";
			}
			else {
				$result = "This email already exists";
			}
			echo $result;
		}

		public function editEmail(Request $request) {

			$email = $request->input('email');
			$u_id = $request->session()->get('user_id');
			$time = date("Y-m-d H:i:s");
			$log = DB::table('gen_users')->where('id', '=', $u_id)->update(['email' => $email]);
			\Session::flash('message', 'You have successfully updated your email id.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('user.personal');
		}
	}
?>
