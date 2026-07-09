<?php
	namespace App\Http\Controllers;
	
	use Illuminate\Http\Request;
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\Redirect;
	use Illuminate\Support\Facades\Route;
	use App\Http\Requests;
	use Illuminate\Mail\Mailer;
	use App\Models\Gen_user;
	use DB;
	use Mail;
	use Session;
	
	class UserController extends Controller {

		public function register(Request $request) {
			
			if($request->session()->get('user_id') != '')
			{
				return redirect()->route('user.personal');
			}
			return view('register');
		}

		public function ajaxValueExist(Request $request) {
			
			$valueType = $request->input('valueType');
			$value = $request->input('value');
			
			$count = DB::table('gen_users')->where($valueType, '=', $value)->count();
			if($count>0){
				if($valueType == 'email')
				{
					$result = "Your email address already exists";
				}
				else if($valueType == 's_id')
				{
					$result = "Your service id is already exist";
				}
			}
			else{
				$result = 'Available.';
			}
			echo $result;
		}

		public function notSignedUp(Request $request) {	//if anyone give the url "(site url)/signed-up" manually, this function will get called.
			
			if($request->session()->get('user_id') == '')
			{
				return redirect()->route('user.login');
			}
			else {
				return redirect()->route('user.personal');
			} 
		}

		public function SignedUp(Request $request) {
			
			$time = time();
			$length = 8;
			$str = "";
			$characters = array_merge(range('A','Z'), range('0','9'));
			$max = count($characters) - 1;
			for ($i = 0; $i < $length; $i++) {
			   $rand = mt_rand(0, $max);
			   $str .= $characters[$rand];
			}
			$string = $str.$time;
			
			$email = $request->input('email');
			$subject = "Activation Code For Personnel Logistic Accounting System";
			
			$genuser = new Gen_user;
			$genuser->email = $request->email;
			$genuser->s_id = $request->s_id;
			$genuser->password = md5($request->password);
			$genuser->auth_code = $string;
			$genuser->save();
			
			$activationUrl = secure_url('/verify-account/'.$string);
			$body = 'Please click on this link '.$activationUrl.' to activate your account.';

			$mailSent = true;
			try {
				Mail::raw('Hello! Welcome to Personnel Logistic Accounting System! '.$body.'',function ($message) use($subject, $email) {
					$message->from(config('mail.from.address'), config('mail.from.name'));
					$message->to($email)->subject($subject);
				});
			} catch (\Throwable $e) {
				$mailSent = false;
			}
			
			return view('register_done', [
				'activationUrl' => $activationUrl,
				'mailSent' => $mailSent,
			]);
		}
		
		public function verifyAccount(Request $request, $string)
		{
			
			$auth_code = $string;
			$active = DB::table('gen_users')->where('auth_code', '=', $auth_code)->count();
			if($active > 0) {
				DB::table('gen_users')->where('auth_code', '=', $auth_code)->update(['auth_code' => NULL,'status' => 1,'activation_status' => 1]);
			}
			return view('activation', array("data"=>$active));
		}
		
		public function UserLogin(Request $request) {
			
			if($request->session()->get('user_id') != '')
			{
				return redirect()->route('user.personal');
			}
			return view('user_login');
		}

		public function forgotPassword(Request $request) {
			
			if($request->session()->get('user_id') != '')
			{
				return redirect()->route('user.personal');
			}
			return view('forgot_password');
		}

		public function ajaxValidUser(Request $request) {
			
			$value = $request->input('value');
			
			$count = DB::table('gen_users')->where('email', '=', $value)->count();
			if($count == 0){
				$result = "Not a valid email";
			}
			else {
				$result = "Submit to get code";
			}
			echo $result;
		}

		public function ajaxSendNewPassword(Request $request) {
			
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
			
			$subject = "Personnel Logistic Accounting System Password Recovery";
			
			$body = 'Your new password is: '.$string.'. ';
			$body.='You can now login using this password. We recommend you to reset this password as soon as you login.';
			try {
				Mail::raw('Hello, user! '.$body.'',function ($message) use($subject, $email) {
					$message->from(config('mail.from.address'), config('mail.from.name'));
					$message->to($email)->subject($subject);
				});
				$rand_password = md5($string);
				DB::table('gen_users')->where('email', '=', $email)->update(['password' => $rand_password]);
				echo "We have sent you an email with your new password. Please go back to the login page to login with your new password.";
			} catch (\Throwable $e) {
				echo "Email could not be sent right now. Please contact the administrator.";
			}
		}

		public function UserLoginCheck(Request $request) {
			
			$s_id = $request->input('s_id');
			$password = md5($request->input('password'));
			$log = DB::table('gen_users')->where('s_id', '=', $s_id)->where('password', '=', $password)->where('activation_status', '=', 1)->where('status', '=', 1)->count();
			if($log == 1) {
				$u_id = DB::table('gen_users')->select('id')->where('s_id', '=', $s_id)->get();
				$user_id = $u_id[0]->id;
				$request->session()->put('user_id', $user_id);
				return redirect()->route('user.personal');
			}
			else{
				\Session::flash('message', '. Please try again.'); 
				\Session::flash('alert-class', 'alert-danger'); 
				return redirect()->route('home');
			}
		}

		public function userLogout(Request $request) {
			
			$request->session()->flush();
			$request->session()->put('user_id', '');
			\Session::flash('message', 'You have successfully logged out.'); 
			\Session::flash('alert-class', 'alert-success');
			return redirect()->route('home');
		}
	}
