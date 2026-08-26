@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-user-circle" aria-hidden="true"></i> Edit Admin Details (Pegawai Pelulus Identity)</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="title"><i class="fa fa-user-circle" aria-hidden="true"></i> Admin Identity:</div>
							<hr>
							<form autocomplete="off" method="post" action="{{ url('/change-adminDetails') }}" name="edit-admin-form" id="edit-admin-form">

								<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
								<input type = "hidden" name = "admin_id" value = "<?php echo $data['admin_id_encoded'] ?? ''; ?>">

								<div class="form-group">
									<label class="label_">Name:</label>
									<input class="form-control" type="text" value="<?php echo isset($data['admin']) ? htmlspecialchars($data['admin']->name, ENT_QUOTES) : ''; ?>" readonly />
								</div>

								<div class="form-group">
									<label class="label_">Email:</label>
									<input class="form-control" type="text" value="<?php echo isset($data['admin']) ? htmlspecialchars($data['admin']->email, ENT_QUOTES) : ''; ?>" readonly />
								</div>

								<div class="form-group">
									<label class="label_">Service ID / No. Tentera:</label>
									<input class="form-control" type="text" id="s_id" name="s_id" maxlength="255"
										   placeholder="e.g. 375292"
										   value="<?php echo isset($data['admin']) && isset($data['admin']->s_id) ? htmlspecialchars($data['admin']->s_id, ENT_QUOTES) : ''; ?>" />
								</div>

								<div class="form-group">
									<label class="label_">Pangkat (Rank):</label>
									<select class="form-control" id="pangkat_id" name="pangkat_id">
										<option value="">-- Choose Rank (Optional) --</option>
										<?php
											$currentPangkatId = (isset($data['admin']) && isset($data['admin']->pangkat_id)) ? (int) $data['admin']->pangkat_id : 0;
										?>
										@if(isset($data['pangkats']))
											@foreach($data['pangkats'] as $rank)
												<?php $id = (int) ($rank->id ?? 0); ?>
												<option value="{{ $id }}" <?php echo ($id === $currentPangkatId && $id > 0) ? 'selected' : ''; ?>>
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
									<label class="label_">Jawatan (Position):</label>
									<input class="form-control" type="text" id="jawatan" name="jawatan" maxlength="255"
										   placeholder="e.g. KETUA STOR PERBENDAHARAAN"
										   value="<?php echo isset($data['admin']) && isset($data['admin']->jawatan) ? htmlspecialchars($data['admin']->jawatan, ENT_QUOTES) : ''; ?>" />
								</div>

								<div class="subBtn">
									<input class="btn btn-default" type="submit" value="SIMPAN (Save)" id="submit" name="submit" />
									<a href="{{ url('/all-admins') }}" class="btn btn-default"> KEMBALI (Back)</a>
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
			$("#edit-admin-form").validate({
				rules: {
					s_id: {
						maxlength: 255,
					},
					jawatan: {
						maxlength: 255,
					},
				},
				submitHandler: function(form) {
					form.submit();
				}
			});
		</script>
	</body>
</html>
