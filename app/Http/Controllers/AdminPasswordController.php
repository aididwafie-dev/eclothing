<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\Redirect;
	use Illuminate\Support\Facades\Route;
	use App\Http\Requests;
	use Session;
	use DB;
	use App\Models\Admin;
	use Illuminate\Mail\Mailer;
	use Mail;
	use App\Support\PasswordHasher;

	class AdminPasswordController extends Controller
	{
	    public function index(Request $request) {

	    	if($request->session()->get('admin_id') == '') {

				return redirect()->route('site-admin.login');
			}
			return view('admin/password_control/passwordChange_admin');
	    }

	    public function saveChangePassword(Request $request) {

	    	$old_password = $request->input('old_password');
			$new_password = $request->input('new_password');
			$admin_id = $request->session()->get('admin_id');
			$time = date("Y-m-d H:i:s");
			$admin = DB::table('admins')->where('id', '=', $admin_id)->first();
			if($admin && PasswordHasher::verify($old_password, $admin->password)) {
				DB::table('admins')->where('id', '=', $admin_id)->update(['password' => PasswordHasher::make($new_password),'updated_at' => $time]);
				\Session::flash('message', 'You have successfully updated your password.'); 
				\Session::flash('alert-class', 'alert-success');
				return redirect()->route('admin.new-admin');
			}
			else {
				\Session::flash('message', 'Wrong password.'); 
				\Session::flash('alert-class', 'alert-danger');
				return redirect()->route('admin.change-password');
			}
	    }

	    public function adminForgotPassword(Request $request) {

	    	if($request->session()->get('admin_id') != '') {
				return redirect()->route('admin.new-admin');
			}
			return view('admin/password_control/forgotPassword_admin');
	    }

	    public function ajaxCheckIfValidEmail(Request $request) {

	    	$value = $request->input('value');
			
			$count = DB::table('admins')->where('email', '=', $value)->count();
			if($count == 0){
				$result = "Not a valid email";
			}
			else {
				$result = "Submit to get code";
			}
			echo $result;
	    }

	    public function ajaxEmailAdminNewPassword(Request $request) {

	    	$email = $request->input('value');
			
			$time = time();
			$length = 8;
			$str = "";
			$characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
			$max = count($characters) - 1;
			for ($i = 0; $i < $length; $i++) {
			   $rand = mt_rand(0, $max);
			   $str .= $characters[$rand];
			}
			$string = $str.$time;
			
			$subject = "Data Collection System Password Recovery of Admin";

			// $server = $_SERVER['SERVER_NAME'];
			// $url = $server.'/admin/verify-code/'.$string;
			// $body = 'Your verification link is '.$url.'';

			$body = 'Your new password is: '.$string.'. ';
			$body.='You can now login using this password. We recommend you to reset this password as soon as you login.';
			try {
				Mail::raw('Hello, admin! '.$body.'',function ($message) use($subject, $email) {
					$message->from(config('mail.from.address'), config('mail.from.name'));
					$message->to($email)->subject($subject);
				});
				$rand_password = PasswordHasher::make($string);
				DB::table('admins')->where('email', '=', $email)->update(['password' => $rand_password]);
				echo "We have sent you an email with your new password. Please go back to the login page to login with your new password.";
			} catch (\Throwable $e) {
				echo "Email could not be sent right now. Please contact the administrator.";
			}
	    }

	  //   public function adminVerifyCodeToResetPassword(Request $request, $string) {
	  //   	$auth_code = $string;
			// $active = DB::table('admins')->where('auth_code', '=', $auth_code)->count();
			// if($active > 0) {
			// 	$username = DB::table('admins')->select('username')->where('auth_code', '=', $auth_code)->get();
			// }
			// else{
			// 	\Session::flash('message', 'This is a invalid url, you can not reset password without the right url.'); 
			// 	\Session::flash('alert-class', 'alert-danger'); 
			// 	return view('home');
			// }
			// return view('admin/password_control/resetPassword_admin', array("data"=>$auth_code));
	  //   }

	  //   public function savePasswordAfterReset(Request $request) {
	  //   	$auth_code = $request->input('auth_code');
			// $new_password = md5($request->input('new_password'));
			// DB::table('admins')->where('auth_code', '=', $auth_code)->update(['password' => $new_password,'auth_code' => NULL]);
			
			// \Session::flash('message', 'Your password is changed successfully. You can now log in with your new password.');
			// \Session::flash('alert-class', 'alert-success');
			// return redirect()->route('site-admin.login');
	  //   }
	}
