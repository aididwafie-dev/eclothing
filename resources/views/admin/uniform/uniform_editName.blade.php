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
								<form autocomplete="off" method="post" action="{{ url('/save-edited-uniformName') }}" name="save-uniformName" id="save-uniformName" enctype="multipart/form-data">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
									<input type="hidden" name="id" value="{{ $uniforms->id }}"/>

				<div class="form-group col-md-3">
				<label class="label_">Uniform type</label>

									<input type="text" class="form-control" name="uniform_type" value="{{ $uniforms->uniform_type }}" required />
									</div>
									
				<div class="form-group col-md-9">
					<label class="label_">Uniform name</label>
					<input type="text" class="form-control" value="{{ $uniforms->uniform_name }}" name="uniform_name" />
				</div>
									
			<div class="form-group col-md-12">

				<label class="label_">Sample photo</label>
				<input class="form-control" name="uniform_image" type="file" accept=".jpg,.png" />
				<span class="text-muted">Please upload only .JPG or .PNG files. The images have to be of correct resolution. We recommend you download <a href="{{ url('front_end/images/uniforms/2.jpg')}}" download>this sample image</a> and modify it in Photoshop.</span>
				<?php if ($uniforms->uniform_photo) { ?>
<img class="img img-thumbnail" src="{{ url("uploads/" . $uniforms->uniform_photo) }}" />
								<?php } else { ?>
<img class="img img-thumbnail" src="{{ url("front_end/images/uniforms/" . $uniforms->uniform_type . ".jpg") }}" />
<?php } ?>
																	</div>
															
									<div class="text-center">
										<input class="btn btn-default btn-success" type="submit" value="SAVE CHANGES" id="submit" name="submit" /> 
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