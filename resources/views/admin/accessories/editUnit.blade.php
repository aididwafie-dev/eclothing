@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
 //echo "<pre>";
 //print_r($data);
 //echo "<pre>";die;
?>

					</br>
					<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit Unit</div>
					<hr>
						<div class="containerMain">
							<div class="content">
									<B>Edit unit details - {{ $units->value }}</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-edited-unit') }}" name="save-unit" id="save-unit">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
									<input type = "hidden" name="id" value = "{{ $units->id }}">
									
									<label class="label_">Unit name</label>
											<input class="form-control" type = "text" name="value" value="{{ $units->value }}" required/>
											
									<div class="subBtn text-center"><br />
										<button class="btn btn-default btn-success" type="submit" id="submit" name="submit">SAVE CHANGES</button>
										<a href="{{ url('/admin/unit') }}" class="btn btn-default"> CANCEL</a>
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