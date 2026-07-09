@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
// echo "<pre>";
// print_r($data);
// echo "<pre>";die;
?>

					</br>
					<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit Uniform type</div>
					<hr>
						<div class="containerMain">
							<div class="content">
								<i class="fa fa-pencil" aria-hidden="true"></i> <B>Uniform Type:</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-edited-uniformName') }}" name="save-uniformName" id="save-uniformName">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
									<input type="hidden" name="id" value="{{ $uniforms->id }}"/>

									<input type="text" class="form-control" name="uniform_type" value="{{ $uniforms->uniform_type }}" required />
									
									<div class="subBtn">
										<input class="btn btn-default" type="submit" value="SAVE" id="submit" name="submit" /> 
										<a href="{{ url('/admin/uniform-edit-cancel') }}" class="btn btn-default"> CANCEL</a>
									</div>
								</form>
							</div>
						</div>
		<!--#### 3 div open in sidebar ####-->
				</div>	
			</div>
		</div>
		<!--#### 3 div open in sidebar ####-->
	</body>
</html>