@include('static-layout/header')
@include('static-layout/sidebar')

					
					</br>
					<div class="title"><i class="fa fa-lock" aria-hidden="true"></i> Change password</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="title"><i class="fa fa-key" aria-hidden="true"></i> Set new password:</div>
							<hr>
							
							<form autocomplete="off" method="post" action="{{ url('/user/edit-password') }}" name="edit-password" id="edit-password">
							
								<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
								
								<div class="form-group">
									<label class="label_">Old Password:</label>
									<input class="form-control" type="password" id="old_password" name="old_password" placeholder="Enter your old password...." />
								</div>
								
								<div class="form-group">
									<label class="label_">New Password:</label>
									<input class="form-control" type="password" id="new_password" name="new_password" placeholder="Enter your new password...." />
								</div>

								<div class="form-group">
									<label class="label_">Confirm Password:</label>							
									<input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password...." />
								</div>

								<div class="subBtn">
									<input class="btn btn-default" type="submit" value="Confirm" id="confirm" name="confirm"/>
									<input class="btn btn-default" type="reset" value="Reset" />
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
								old_password: "You haven't provided any password",
								new_password: "Provided a valid password with min length of 8",
								confirm_password: "Please put the same password as you provided in the upper box",
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
