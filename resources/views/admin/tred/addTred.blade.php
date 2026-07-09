@include('static-layout/header')
@include('static-layout/admin_sidebar')

						</br>
						<div class="title"><i class="fa fa-plus-square" aria-hidden="true"></i> Add new tred</B></div>
						<hr>
						<div class="containerMain">
							<div class="content">
									<i class="fa fa-plus-circle" aria-hidden="true"></i> <B>New tred</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-added-tred') }}" name="save-added-tred" id="save-added-tred">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
									<label class="label_">Tred name</label>
									<input type="text" class="form-control" name="tred_name" required />
<br />
								<label class="label_">Officer recruit</label>
										<select class="form-control" name="officer_recruit">
											<option value="1">Officer</option>
											<option value="2">Other rank</option>
											<option value="3">Both officer & other rank</option>
											</select>
											
									<div class="subBtn text-center"><br />
										<button class="btn btn-default btn-success" type="submit" id="submit" name="submit">SAVE TRED</button>
										<a href="{{ url('/admin/tred') }}" class="btn btn-default"> CANCEL</a>
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