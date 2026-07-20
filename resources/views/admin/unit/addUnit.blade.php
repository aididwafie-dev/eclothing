@include('static-layout/header')
@include('static-layout/admin_sidebar')

						</br>
						<div class="title"><i class="fa fa-plus-square" aria-hidden="true"></i> Add new unit</B></div>
						<hr>
						<div class="containerMain">
							<div class="content">
									<i class="fa fa-plus-circle" aria-hidden="true"></i> <B>New unit name</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-added-unit') }}" name="save-added-unit" id="save-added-unit">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
									<label class="label_">Unit name:</label>
									<input type="text" class="form-control" name="unit_name" required />

									<div class="subBtn text-center"><br />
										<button class="btn btn-primary" type="submit" id="submit" name="submit">SAVE UNIT</button>
										<a href="{{ url('/admin/unit') }}" class="btn btn-default"> CANCEL</a>
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