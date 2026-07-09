@include('static-layout/not-logged-header')

		<div class="banner">
			<div class="content content-log">
				<img src="{{ $siteLogoUrl ?? asset('front_end/images/logo.png') }}" class="img-responsive center-block" alt="logo" />
				<!-- <h2 class="text-center">Personnel Logistic Accounting System</h2> -->

				<div class="title">Login for users</div>
				<hr>
				
				<form autocomplete="off" method="post" action="{{ url('/user/login-check') }}" name="login-form" id="login-form">
				
					<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">


					<div class="form-group">
						<label class="label_">Service ID:</label>
						<input class="form-control" type="text" id="s_id" name="s_id" placeholder="Enter your Service ID...." />
					</div>
							
					<div class="form-group">
						<label class="label_">Password:</label>
						<input class="form-control" type="password" id="password" name="password" placeholder="Enter your password...." />
					</div>

					<div class="subBtn">
						<input class="btn btn-default" type="submit" value="Sign in" id="submit" name="submit" /> 
						<input class="btn btn-default" type="reset" value="Reset" />
					</div>

				</form>
				<span>Not registered yet? <a href="{{ url('/register') }}" class="redirect-link">Click to register.</a></span>
				<br>
				<span><a href="{{ url('/forgot-password') }}" class="redirect-link">Forgot Password?</a></span>
				<br/>
				<a class="back-btn" href="{{ url('/home') }}"><i class="fa fa-long-arrow-left" aria-hidden="true"></i> Home</a>
			</div>
		</div>

		
		<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
		<script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js"></script>
		<style type="text/css">
			label.error{ color:red; }
			input.error{ border:  1px solid red; }
		</style>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#login-form").validate({
					rules: {
								s_id: {
									required: true,
								},
								password: {
									required: true,
								},
							},
							messages: {
								s_id: "Enter your valid service ID",
								password: "Enter your password",
							},
					submitHandler: function(form) {
						// do other things for a valid form
						form.submit();
					}
				});
			});
			
		</script>
	</body>
</html>
