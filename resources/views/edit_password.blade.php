@include('static-layout/header')
@include('static-layout/sidebar')


					</br>
					<div class="title"><i class="fa fa-lock" aria-hidden="true"></i> {{ __('app.account.change_password_title') }}</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="title"><i class="fa fa-key" aria-hidden="true"></i> {{ __('app.account.set_new_password') }}</div>
							<hr>

							<form autocomplete="off" method="post" action="{{ url('/user/edit-password') }}" name="edit-password" id="edit-password">

								<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>

								<div class="form-group">
									<label class="label_">{{ __('app.account.old_password') }}</label>
									<input class="form-control" type="password" id="old_password" name="old_password" placeholder="{{ __('app.account.old_password_placeholder') }}" />
								</div>

								<div class="form-group">
									<label class="label_">{{ __('app.account.new_password') }}</label>
									<input class="form-control" type="password" id="new_password" name="new_password" placeholder="{{ __('app.account.new_password_placeholder') }}" />
								</div>

								<div class="form-group">
									<label class="label_">{{ __('app.account.confirm_password') }}</label>
									<input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="{{ __('app.account.confirm_password_placeholder') }}" />
								</div>

								<div class="subBtn">
									<input class="btn btn-default" type="submit" value="{{ __('app.account.confirm') }}" id="confirm" name="confirm"/>
									<input class="btn btn-default" type="reset" value="{{ __('app.account.reset') }}" />
								</div>

							</form>
						</div>
					</div>
<!--#### 3 div open in sidebar ####-->
				</div>
			</div>
		</div>
<!--#### 3 div open in sidebar ####-->

		<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
		<script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js"></script>
		<style type="text/css">
			label.error{ color:red; }
			input.error{ border:  1px solid red; }
		</style>
		<script type="text/javascript">
			$(document).ready(function(){

				$("#edit-password").validate({
					rules: {
								old_password: {
									required: true,
								},
								new_password: {
									required: true,
								   minlength: 8,
								},
								confirm_password: {
									required: true,
									equalTo: "#new_password",
								},
							},
							messages: {
								old_password: {!! json_encode(__('app.account.old_password_required')) !!},
								new_password: {!! json_encode(__('app.account.new_password_invalid')) !!},
								confirm_password: {!! json_encode(__('app.account.password_mismatch')) !!},
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
