@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
 //echo "<pre>";
 //print_r($data);
 //echo "<pre>";die;
?>

					</br>
					<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit Clothes details of Uniform <B>{{ $data['uniforms']->uniform_type }}</B></div>
					<hr>
						<div class="containerMain">
							<div class="content">
									<B>Edit cloth details</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-edited-clothes') }}" name="save-clothes" id="save-clothes">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
									<input type = "hidden" name = "uniform_id" value = "{{ $data['uniforms']->id }}">
									
									<input type = "hidden" name="id" value = "{{ $data['clothes'][0]->id }}">
									@foreach($data['clothes'][0] as $key => $value)
										@if($key == 'clothes_type')
										
											<label class="label_">Cloth:</label>
											<input class="form-control" type = "text" id="{{ $key }}" name="{{ $key }}" value="{{ $value }}" required/>
										@endif
										
										@if($key == 'clothes_size')
											<label class="label_">Size:</label>
											<input class="form-control" type = "text" id="{{ $key }}" name="{{ $key }}" value="{{ $value }}" required/>
										@endif
									@endforeach
									
									<?php
										$stringId = "DCS".$data['uniforms']->id."DCS";
										$uniformId = base64_encode($stringId);
									?>
									<div class="subBtn text-center"><br />
										<button class="btn btn-primary" type="submit" id="submit" name="submit">SAVE CHANGES</button>
										<a href="{{ url('/admin/clothes-edit-cancel/'.$uniformId) }}" class="btn btn-default"> CANCEL</a>
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