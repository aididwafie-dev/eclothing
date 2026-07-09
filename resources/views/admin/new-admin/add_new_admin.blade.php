@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-user-plus" aria-hidden="true"></i> Add new Admin user</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="title"><i class="fa fa-user-circle" aria-hidden="true"></i> Service ID of new admin user</div>
							<hr>
							<form autocomplete="off" method="post" action="{{ url('/get-new-admin-details') }}" name="new-admin-form" id="new-admin-form">
							
								<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">

								<div class="form-group">
									<label class="label_">Service ID</label>
									<input class="form-control" type="text" id="s_id" name="s_id" placeholder="Enter the service ID of the new admin user" />
								</div>

								<div class="subBtn">
									<input class="btn btn-default" type="submit" value="Set as admin" id="submit" name="submit" /> 
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
			$("#new-admin-form").validate({
				rules: {
							s_id: {
								required: true,
							   maxlength: 7,
								  number: true,
							},
						},
						messages: {
							s_id: "Put your valid service ID who is already registered",
						},
				submitHandler: function(form) {
					// do other things for a valid form
					form.submit();
				}
			});
			$(document).ready(function(){
			});
		</script>
	</body>
</html>
