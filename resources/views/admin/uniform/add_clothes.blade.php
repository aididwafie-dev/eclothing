@include('static-layout/header')
@include('static-layout/admin_sidebar')

						</br>
						<div class="title"><i class="fa fa-plus-square" aria-hidden="true"></i> Add New Cloth to uniform <B>{{ $uniforms->uniform_type }}</B></div>
						<hr>
						<div class="containerMain">
							<div class="content">
									<i class="fa fa-plus-circle" aria-hidden="true"></i> <B>New Cloth details:</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-added-cloth') }}" name="save-added-cloth" id="save-added-cloth">
					
									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>"/>
									<input type="hidden" name="id" value="{{ $uniforms->id }}"/>
				<div class="form-group col-sm-12">
									<label class="label_">Cloth type:</label>
									<input type="text" class="form-control" name="clothes_type" required />
									For accessories, please add under 'Accessories' option
									<br />
									</div>
				<div class="form-group col-sm-12">
					<label class="label_">Cloth size:</label>
									<input type="text" class="form-control" name="clothes_size" id="clothes_size" required />
									</div>									
									<div class="col-sm-6">
					<label class="label_">Gender:</label>
					<!--Jantina--><br />
					<select multiple id="jantina" name="jantina[]">
						@foreach($jantinas as $jantina)
						<option value="{{ $jantina->id }}">{{ $jantina->value }}</option>
						@endforeach
					</select>
					<br />
				</div>

				<div class="col-sm-6">
					<label class="label_">Rank:</label>
					<!--Jantina--><br />
					<select multiple id="pangkat" name="pangkat[]">
						@foreach($pangkats as $pangkat)
						<option value="{{ $pangkat->id }}">{{ $pangkat->value }}</option>
						@endforeach
					</select>
					<br />
				</div>

			
				<div class="col-sm-6">
					<label class="label_">Tred:</label>
					<!--Jantina--><br />
					<select multiple id="ketukangan" name="ketukangan[]">
						@foreach($ketukangans as $ketukangan)
						<option value="{{ $ketukangan->id }}">{{ $ketukangan->value }} - {{ $ketukangan->officer_recruit == 1 ? "OFFICER" : ($ketukangan->officer_recruit == 2 ? "RECRUIT" : "BOTH") }}</option>
						@endforeach
					</select>
				</div>

				<div class="form-group col-sm-6">
					<label class="label_">Religion:</label>
					<!--Status Religion--><br />
					<select multiple id="religion" name="religion[]">
						<option value="">All religions</option>
						<option value="ISLAM" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "ISLAM" ? "selected" : ""; ?>>ISLAM</option>
						<option value="BUDDHISM" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "BUDDHISM" ? "selected" : ""; ?>>BUDDHISM</option>
						<option value="HINDU" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "HINDU" ? "selected" : ""; ?>>HINDU</option>
						<option value="CHRISTIANITY" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "CHRISTIANITY" ? "selected" : ""; ?>>CHRISTIANITY</option>
					</select>
				</div>
								
			<div class="col-sm-12">Leave the four boxes blank to not filter and show to all.</div>
									<?php
										$stringId = "DCS".$uniforms->id."DCS";
										$uniformId = base64_encode($stringId);
									?>
									<div class="text-center"><br />
										<button class="btn btn-default btn-success" type="submit" id="submit" name="submit">SAVE CLOTH</button>
										<a href="{{ url('/admin/clothes-add-cancel/'.$uniformId) }}" class="btn btn-default"> CANCEL</a>
									</div>
								</form>
							</div>
						</div>
		<!--#### 3 div open in sidebar ####-->
				</div>	
			</div>
		</div>
		<!--#### 3 div open in sidebar ####-->
		<script type="text/javascript">
			$('#clothes_size').keyup(function(){
				var clothes_size = $(this).val().toUpperCase();
				$(this).val(clothes_size);
			});
		</script>
	</body>
<script>
	$(document).ready(function() {
		$('select#jantina').multipleSelect({
			formatSelectAll: function() {
				return '[For both]'
			},
			width: "100%"
		});
		$('select#pangkat').multipleSelect({
			formatSelectAll: function() {
				return '[For all ranks]'
			},
			width: "100%"
		});
		$('select#ketukangan').multipleSelect({
			formatSelectAll: function() {
				return '[For all treds]'
			},
			width: "100%"
		});
		$('select#religion').multipleSelect({
			formatSelectAll: function() {
				return '[For all religions]'
			},
			width: "100%"
		});
	});

</script>
<link rel="stylesheet" href="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.css">
<script src="https://unpkg.com/multiple-select@1.5.2/dist/multiple-select.min.js"></script>
</html>