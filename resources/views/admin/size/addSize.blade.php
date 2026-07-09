@include('static-layout/header')
@include('static-layout/admin_sidebar')

						</br>
						<div class="title"><i class="fa fa-plus-square" aria-hidden="true"></i> Add new size</B></div>
						<hr>
						<div class="containerMain">
							<div class="content">
									<i class="fa fa-plus-circle" aria-hidden="true"></i> <B>New size name</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-added-size') }}" name="save-added-size" id="save-added-size">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
									<label class="label_">Size name:</label>
									<input type="text" class="form-control" name="size_name" required />

									<div class="subBtn text-center"><br />
										<button class="btn btn-default btn-success" type="submit" id="submit" name="submit">SAVE SIZE</button>
										<a href="{{ url('/admin/size') }}" class="btn btn-default"> CANCEL</a>
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