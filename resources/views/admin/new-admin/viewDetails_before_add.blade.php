@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-user-plus" aria-hidden="true"></i> Add New Admin</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="title"><i class="fa fa-user-circle" aria-hidden="true"></i> Details of New Admin:</div>
							<hr>
							<form autocomplete="off" method="post" action="{{ url('/add-admin') }}" name="new-admin-form" id="new-admin-form">
							
								<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">

								<div class="form-group">
									<label class="label_">Name:</label>
									<input class="form-control" type="text" id="name" name="name" value="{{$data['personalDetails_newAdmin']->name}}" readonly />
								</div>

								<div class="form-group">
									<label class="label_">Email:</label>
									<input class="form-control" type="text" id="email" name="email" value="{{$data['genDetails_newAdmin']->email}}" readonly />
								</div>

								<div class="form-group">
									<label class="label_">Username:</label>
									<input class="form-control" type="text" id="username" name="username" value="{{$data['genDetails_newAdmin']->s_id}}" readonly />
								</div>

								<input class="form-control" type="hidden" id="password" name="password" value="{{$data['genDetails_newAdmin']->password}}" />

								<div class="form-group">
									<label class="label_">Service ID / No. Tentera (for approver signature):</label>
									<input class="form-control" type="text" id="s_id" name="s_id" maxlength="255"
										   placeholder="e.g. {{$data['genDetails_newAdmin']->s_id}}"
										   value="{{$data['genDetails_newAdmin']->s_id ?? ''}}" />
								</div>

								<div class="form-group">
									<label class="label_">Pangkat (Rank for Pegawai Pelulus signatory):</label>
									<select class="form-control" id="pangkat_id" name="pangkat_id">
										<option value="">-- Choose Rank (Optional) --</option>
										@if(isset($data['pangkats']))
											@foreach($data['pangkats'] as $rank)
												<?php $id = (int) ($rank->id ?? 0); ?>
												<option value="{{ $id }}"
													<?php
														if (isset($data['personalDetails_newAdmin']->pangkat)
															&& (int) $data['personalDetails_newAdmin']->pangkat === $id
															&& $id > 0) {
															echo 'selected';
														}
													?>
												>
													{{ htmlspecialchars(trim((string)($rank->value ?? '')), ENT_QUOTES) }}
													<?php
														$ofc = (int) ($rank->officer_recruit ?? 0);
														if ($ofc === 1) echo ' [Officer]';
														else if ($ofc === 2) echo ' [Other Rank]';
													?>
												</option>
											@endforeach
										@endif
									</select>
								</div>

								<div class="form-group">
									<label class="label_">Jawatan (Position for Pegawai Pelulus):</label>
									<input class="form-control" type="text" id="jawatan" name="jawatan" maxlength="255"
										   placeholder="e.g. PEGAWAI TADBIR GRED W29"
										   value="" />
								</div>

								<div class="subBtn">
									<input class="btn btn-default" type="submit" value="ADD" id="submit" name="submit" />
									<a href="{{ url('/new-admin') }}" class="btn btn-default"> CANCEL</a>
								</div>
							</form>
						</div>
					</div>
<!--#### 3 div open in sidebar ####-->
				</div>	
			</div>
		</div>
<!--#### 3 div open in sidebar ####-->
		<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
		<script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js"></script>
		<script type="text/javascript">
			$(document).ready(function(){
    			$("#new-admin-form").submit(function(){
					var rply = confirm('Are you sure you want to add this user as a admin?');
					if(rply == true) {
						return true;
					}
					else{
						return false;
					}
				});
			});
		</script>
	</body>
</html>