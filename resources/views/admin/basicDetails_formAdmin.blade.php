@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-address-card" aria-hidden="true"></i> Users Basic Details</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="title"><i class="fa fa-pencil" aria-hidden="true"></i> Edit Basic Details:</div>
							<hr>
							
							<form autocomplete="off" method="post" action="{{ url('/change-basicDetails') }}" name="basic-details-form" id="basic-details-form">
							
								<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">

								<input type = "hidden" name="id" value = "{{ $user_details->id }}">

								<div class="form-group">
									<label class="label_">Service ID:</label>
									<input class="form-control" type="text" id="s_id" name="s_id" value = "{{ $user_details->s_id }}"  placeholder="Write new service id...." />
								</div>
								
								<div class="form-group">
									<label class="label_">Password:</label>
									<input class="form-control" type="password" id="password" name="password" value = "{{ $user_details->password }}" placeholder="Write new password...." />
								</div>

								<div class="subBtn">
									<input class="btn btn-default" type="submit" value="Save" id="submit" name="submit" />
									<a class="btn btn-default" href="{{ url('/admin-cancel') }}">CANCEL</a>
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
			$("#basic-details-form").validate({
				rules: {
							
							password: {
								required: true,
							   minlength: 8,
							},
							s_id: {
								required: true,
							   maxlength: 7,
								  number: true,
							},
						},
						messages: {
							password: "Put a valid password",
							s_id: "Put a valid service id",
						},
				submitHandler: function(form) {
					// do other things for a valid form
					form.submit();
				}
			});
		</script>
	</body>
</html>