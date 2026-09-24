@include('static-layout/not-logged-header')

		<div class="banner">
			<div class="content content-log">
				<img src="{{ $siteLogoUrl ?? asset('front_end/images/logo.png') }}" class="img-responsive center-block" alt="logo" />
				<!-- <h2 class="text-center">Personnel Logistic Accounting System</h2> -->
				<div class="title">{{ __('app.register.title') }}</div>
				<hr>

				<form autocomplete="off" method="post" action="{{ url('/signed-up') }}" name="register-form" id="register-form">

					<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">

						<div class="form-group">
							<label class="label_">{{ __('app.register.email') }}</label>
							<input class="form-control" type="email" id="email" name="email" placeholder="{{ __('app.register.email_placeholder') }}"/>
							<div id="result_e"></div>
						</div>

						<div class="form-group">
							<label class="label_">{{ __('app.login.service_id') }}</label>
							<input class="form-control" type="text" id="s_id" name="s_id" placeholder="{{ __('app.login.service_id_placeholder') }}" />
							<div id="result_s"></div>
						</div>

						<div class="form-group">
							<label class="label_">{{ __('app.login.password') }}</label>
							<input class="form-control" type="password" id="password" name="password" placeholder="{{ __('app.register.password_placeholder') }}"/>
						</div>

						<div class="form-group">
							<label class="label_">{{ __('app.register.confirm_password') }}</label>
							<input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="{{ __('app.register.confirm_password_placeholder') }}" />
						</div>

						<div class="subBtn">
							<input class="btn btn-default" type="submit" id="submit" name="submit" value="{{ __('app.register.confirm') }}" />
							<input class="btn btn-default" id="reset" type="reset" value="{{ __('app.register.reset') }}" />
						</div>
				</form>
				<span>{{ __('app.register.already_registered') }} <a href="{{ url('/user/login') }}" class="redirect-link">{{ __('app.register.click_to_login') }}</a></span>
				<br/>
				<a class="back-btn" href="{{ url('/home') }}"><i class="fa fa-long-arrow-left" aria-hidden="true"></i> {{ __('app.register.home') }}</a>
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
				var checkEmail = 1;
				var checkService = 1;
    			$("#email").keyup(function(){
    				$('#submit').prop('disabled', true);
        			var value = $("#email").val();
					var valueType = "email";
					checkExistFunction(valueType, value);
    			});

				$("#s_id").keyup(function(){
					$('#submit').prop('disabled', true);
        			var value = $("#s_id").val();
					var valueType = "s_id";
					checkExistFunction(valueType, value);
    			});

				function checkExistFunction(valueType, value)
				{
					$.ajaxSetup({
						headers: {
							'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
						}
					});
           			$.ajax({
						type:'post',
						url:'/value-exist',
						data:{valueType:valueType, value:value},
						success:function(data) {
							if(valueType == 'email') {
								if(data == "Your email address already exists") {
									$('#submit').prop('disabled', true);
									$("#result_e").html(data);
									checkEmail = 0;
								}
								else {
									$("#result_e").html('');
									checkEmail = 1;
									makeEnableFunction();
								}
							}
							else if(valueType == 's_id') {
								if(data == "Your service id is already exist") {
									$('#submit').prop('disabled', true);
									$("#result_s").html(data);
									checkService = 0;
								}
								else {
									$("#result_s").html('');
									checkService = 1;
									makeEnableFunction();
								}
							}
						}
					});
				}

				function makeEnableFunction() {

					if(checkEmail == 1 && checkService == 1){
						$('#submit').prop('disabled', false);
						$("#result_e").html('');
						$("#result_s").html('');
					}
				}

				$("#reset").click(function() {
					checkEmail = 1;
					checkService = 1;
					makeEnableFunction();
    			});
			});

			$("#register-form").validate({
				rules: {
							email: {
								required: true,
							},
							s_id: {
								required: true,
							   maxlength: 7,
								  number: true,
							},
							password: {
								required: true,
							   minlength: 8,
							},
							confirm_password: {
								required: true,
								equalTo: "#password",
							},
						},
						messages: {
							email: {!! json_encode(__('app.register.invalid_email')) !!},
							s_id: {!! json_encode(__('app.register.invalid_service_id')) !!},
							password: {!! json_encode(__('app.register.invalid_password')) !!},
							confirm_password: {!! json_encode(__('app.register.password_mismatch')) !!},
						},
				submitHandler: function(form) {
					// do other things for a valid form
					form.submit();
				}
			});
		</script>
	</body>
</html>
