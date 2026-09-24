@include('static-layout/header')
@include('static-layout/sidebar')

					</br>
					<div class="title"><i class="fa fa-envelope-square" aria-hidden="true"></i> {{ __('app.account.change_email_title') }}</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="title"><i class="fa fa-envelope" aria-hidden="true"></i> {{ __('app.account.set_new_email') }}</div>
							<hr>

							<form autocomplete="off" method="post" action="{{ url('/user/edit-email') }}" name="edit-email" id="edit-email">

								<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>

								<div class="form-group">
									<label class="label_">{{ __('app.account.new_email') }}</label>
									<input class="form-control" type="email" id="email" name="email" placeholder="{{ __('app.account.new_email_placeholder') }}" />
									<div id="result_e"></div>
								</div>

								<div class="subBtn">
									<input class="btn btn-default" type="submit" value="{{ __('app.account.confirm') }}" id="confirm"/>
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

    			$("#email").keyup(function(){
        			var value = $("#email").val();
					$.ajaxSetup({
						headers: {
							'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
						}
					});
           			$.ajax({
						type:'post',
						url:'/if-already-exists',
						data:{value:value},
						success:function(data) {
							if(data == "This email already exists"){
								$('#confirm').prop('disabled', true);
								$("#result_e").html(data);
							}
							else{
								$('#confirm').prop('disabled', false);
								$("#result_e").html('');
							}
						}
					});
				});

				$("#edit-email").validate({
					rules: {
								email: {
									required: true,
								},
							},
							messages: {
								email: {!! json_encode(__('app.account.email_invalid')) !!},
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
