@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit Size</div>
					<hr>
						<div class="containerMain">
							<div class="content">
									<B>Edit size details - {{ $sizes->value }}</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-edited-size') }}" name="save-size" id="save-size">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
									<input type = "hidden" name="id" value = "{{ $sizes->id }}">
									
									<label class="label_">Size name</label>
											<input class="form-control" type = "text" name="value" value="{{ $sizes->value }}" required/>
											
									<div class="subBtn text-center"><br />
										<button class="btn btn-default btn-success" type="submit" id="submit" name="submit">SAVE CHANGES</button>
										<a href="{{ url('/admin/size') }}" class="btn btn-default"> CANCEL</a>
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