@include('static-layout/not-logged-header')
		
		<div class="banner">
			<div class="content content-log">
				<img src="{{ $siteLogoUrl ?? asset('front_end/images/logo.png') }}" class="img-responsive center-block" alt="logo" />
				<!-- <h2 class="text-center">Personnel Logistic Accounting System</h2> -->
				<div class="title">Reset Password</div>
				<hr>		
								
				<div class="form-group">
					<label class="label_">E-mail</label>
					<input class="form-control" autocomplete="off" type="email" id="email" name="email" placeholder="Enter your E-mail address"/>
					<div id="result_e"></div>
				</div>

				<div class="subBtn">
					<button class="btn btn-default" value="SEND" id="send" name="send">SEND</button>
					<div id="passwordLoader" style="display:none;">
						<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
						<span>Loading...</span>
					</div>
				</div>
				<br/>
				<a class="back-btn" href="{{ url('/user/login') }}"><i class="fa fa-long-arrow-left" aria-hidden="true"></i> User Login</a>
				<div id="code_send"></div>
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
				$('#send').prop('disabled', true);
    			$("#email").keyup(function(){
        			var value = $("#email").val();
					$.ajaxSetup({
						headers: {
							'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
						}
					});
           			$.ajax({
						type:'post',
						url:'/if-valid',
						data:{value:value},
						success:function(data) {
							if(data == "Not a valid email"){
								$('#send').prop('disabled', true);
								$("#result_e").html(data);
							}
							else{
								$('#send').prop('disabled', false);
								$("#result_e").html('');
							}
						}
					});
				});
				
				$("#send").click(function(){
					
        			var value = $("#email").val();
					$('#send').prop('disabled', true);
					$("#passwordLoader").fadeIn('show');
					
					$.ajaxSetup({
						headers: {
							'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
						}
					});
           			$.ajax({
						type:'post',
						url:'/send-code',
						data:{value:value},
						success:function(data) {
							$("#code_send").html(data);
							$("#email").val('');
							$('#send').prop('disabled', false);
							$("#passwordLoader").fadeOut('show');
							// window.setTimeout(function() {
							// 	$("#code_send").html('');
							// }, 3000);
						}
					});
				});
			});
			$("#forgot-password").validate({
				rules: {
							email: {
								required: true,
							},
						},
						messages: {
							email: "Put your valid email address",
						},
				submitHandler: function(form) {
					// do other things for a valid form
					form.submit();
				}
			});
		</script>
	</body>
</html>
