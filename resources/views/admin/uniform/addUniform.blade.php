@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-plus-square" aria-hidden="true"></i> Add new uniform</B></div>
<hr>
<div class="containerMain">
	<div class="content">
		<i class="fa fa-plus-circle" aria-hidden="true"></i> <B>New uniform</B>
		<hr>
		<form autocomplete="off" method="post" action="{{ url('/save-added-uniform') }}" name="save-added-size" id="save-added-size" enctype="multipart/form-data">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>" />
				<div class="form-group col-md-3">
					<label class="label_">Uniform type</label>
					<input type="text" class="form-control text-uppercase" name="uniform_type" required maxlength="2" />
				</div>

				<div class="form-group col-md-9">
					<label class="label_">Uniform name</label>
					<input type="text" class="form-control" name="uniform_name" />
				</div>

			<div class="form-group col-md-12">

				<label class="label_">Sample photo</label>
				<input class="form-control" name="uniform_image" type="file" accept=".jpg,.png" />
				<span class="text-muted">Please upload only .JPG or .PNG files. The images have to be of correct resolution. We recommend you download <a href="{{ url('front_end/images/uniforms/2.jpg')}}" download>this sample image</a> and modify it in Photoshop.</span>
			</div>


			<div class="text-center"><br />
				<button class="btn btn-primary" type="submit" id="submit" name="submit">SAVE UNIFORM</button>
				<a href="{{ url('/admin/uniform') }}" class="btn btn-default"> CANCEL</a>
			</div>
		</form>
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
</body>

</html>
