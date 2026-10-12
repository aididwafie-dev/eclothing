@include('static-layout/header')
@include('static-layout/admin_sidebar')

						</br>
						<div class="title"><i class="fa fa-plus-square" aria-hidden="true"></i> Add new base</B></div>
						<hr>
						<div class="containerMain">
							<div class="content">
									<i class="fa fa-plus-circle" aria-hidden="true"></i> <B>New base</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-added-base') }}" name="save-added-base" id="save-added-base">

									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
									<label class="label_">Base name</label>
									<input type="text" class="form-control" name="base_name" maxlength="255" required />
<br />
									<label class="label_">Org name</label>
									<input type="text" class="form-control" name="org_name" maxlength="255" />
<br />
								<label class="label_">Status</label>
										<select class="form-control" name="status">
											<option value="1">Active</option>
											<option value="0">Inactive</option>
											</select>

									<div class="subBtn text-center"><br />
										<button class="btn btn-primary" type="submit" id="submit" name="submit">SAVE BASE</button>
										<a href="{{ url('/admin/base') }}" class="btn btn-default"> CANCEL</a>
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
