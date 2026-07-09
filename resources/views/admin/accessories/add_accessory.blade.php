@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> Add accessory for Uniform <B>{{$uniform->uniform_type}}</B></div>
<hr>
<div class="containerMain">
	<div class="content">
		<i class="fa fa-plus-circle" aria-hidden="true"></i> <B>New accessory details:</B>
		<hr>
		<form autocomplete="off" method="post" action="{{ url('/save-added-accessory') }}" name="save-added-accessory" id="save-added-accessory">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>" />
			<input type="hidden" name="id" value="{{ $uniform->id }}" />
			<div class="row">
				<div class="col-sm-12">
					<label class="label_">Accessory name:</label>
					<input type="text" class="form-control" name="accessories_type" required />
					<br />
				</div>
				<div class="col-sm-12">
					<label class="label_">Accessory options:</label>
					<input type="text" class="form-control" name="accessories_size" id="accessories_size" />
				<small>For Yes / No selection checkbox leave this box blank.</small>
					<br />
				</div>

				<div class="col-sm-6">
					<label class="label_">Gender:</label>
					<!--Jantina-->
					<select class="form-control" id="jantina" name="jantina">
						<option value="">All genders</option>
						@foreach($jantinas as $jantina)
						<option value="{{ $jantina->id }}">{{ $jantina->value }}</option>
						@endforeach
					</select>
					<br />
				</div>

				<div class="col-sm-6">
					<label class="label_">Rank:</label>
					<!--Jantina-->
					<select class="form-control" id="pangkat" name="pangkat">
						<option value="">All ranks</option>
						@foreach($pangkats as $pangkat)
						<option value="{{ $pangkat->id }}">{{ $pangkat->value }}</option>
						@endforeach
					</select>
					<br />
				</div>

			
				<div class="col-sm-6">
					<label class="label_">Tred:</label>
					<!--Jantina-->
					<select class="form-control" id="ketukangan" name="ketukangan">
						<option value="">All treds</option>
						@foreach($ketukangans as $ketukangan)
						<option value="{{ $ketukangan->id }}">{{ $ketukangan->value }} - {{ $ketukangan->officer_recruit == 1 ? "OFFICER" : ($ketukangan->officer_recruit == 2 ? "RECRUIT" : "BOTH") }}</option>
						@endforeach
					</select>
				</div>

				<div class="form-group col-sm-6">
					<label class="label_">Religion:</label>
					<!--Status Religion-->
					<select class="form-control" id="religion" name="religion">
						<option value="">All religions</option>
						<option value="ISLAM" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "ISLAM" ? "selected" : ""; ?>>ISLAM</option>
						<option value="BUDDHISM" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "BUDDHISM" ? "selected" : ""; ?>>BUDDHISM</option>
						<option value="HINDU" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "HINDU" ? "selected" : ""; ?>>HINDU</option>
						<option value="CHRISTIANITY" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "CHRISTIANITY" ? "selected" : ""; ?>>CHRISTIANITY</option>
						<option value="" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion != "" && $data['personal_detail']->religion != "ISLAM" && $data['personal_detail']->religion != "BUDDHISM" && $data['personal_detail']->religion != "HINDU" && $data['personal_detail']->religion != "CHRISTIANITY" ? "selected" : ""; ?>>OTHERS</option>
					</select>
				</div>

				<?php
				$stringId = "DCS".$uniform->id."DCS";
				$uniformId = base64_encode($stringId);
				?>

				<div class="text-center"><br />
					<button class="btn btn-default btn-success" type="submit" id="submit" name="submit">SAVE CHANGES</button>
					<a href="{{ url('/admin/accessories/'.$uniformId) }}" class="btn btn-default"> CANCEL</a>
				</div>

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
	$('#accessories_size').keyup(function() {
		var accessories_size = $(this).val().toUpperCase();
		$(this).val(accessories_size);
	});

</script>
</body>

</html>
