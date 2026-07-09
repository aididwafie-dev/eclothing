@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit accessory <B>{{ $accessories->clothes_type }}</B>  for Uniform <B>{{$uniform->uniform_type}}</B></div>
<hr>
<div class="containerMain">
	<div class="content">
		<B>Edit accessory details</B>
		<hr>
		<form autocomplete="off" method="post" action="{{ url('/save-edited-accessory') }}" name="save-edited-accessory" id="save-edited-accessory">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">

			<input type="hidden" name="id" value="{{ $accessories->id }}">
			<input type="hidden" name="uniform_id" value="{{ $accessories->uniforms_id }}">

			<div class="col-sm-12">
				<label class="label_">Accessory name:</label>
				<input type="text" class="form-control" name="accessories_type" required value="{{ $accessories->clothes_type }}" />
				<br />
			</div>
			<div class="col-sm-12">
				<label class="label_">Accessory options:</label>
				<input type="text" class="form-control" name="accessories_size" id="accessories_size" value="{{ $accessories->clothes_size }}" />
				<small>For Yes / No selection checkbox leave this box blank.</small>
				<br />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Gender:</label>
				<!--Jantina-->
				<select class="form-control" id="jantina" name="jantina">
					<option value="">All genders</option>
					@foreach($jantinas as $jantina)
					<option value="{{ $jantina->id }}" <?php if ($accessories->jantina == $jantina->id) { ?>selected<?php }?>>{{ $jantina->value }}</option>
					@endforeach
				</select>
					<br />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Rank:</label>
				<!--Jantina-->
				<select class="form-control" id="pangkat" name="pangkat">
					<option value="">All pangkat</option>
					@foreach($pangkats as $pangkat)
					<option value="{{ $pangkat->id }}" <?php if ($accessories->pangkat == $pangkat->id) { ?>selected<?php }?>>{{ $pangkat->value }}</option>
					@endforeach
				</select>
					<br />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Tred:</label>
				<!--Jantina-->
				<select class="form-control" id="ketukangan" name="ketukangan">
					<option value="">All treds</option>
					@foreach($ketukangans as $ketukangan)
					<option value="{{ $ketukangan->id }}" <?php if ($accessories->ketukangan == $ketukangan->id) { ?>selected<?php }?>>{{ $ketukangan->value }} - {{ $ketukangan->officer_recruit == 1 ? "OFFICER" : ($ketukangan->officer_recruit == 2 ? "RECRUIT" : "BOTH") }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Religion:</label>
				<!--Status Religion-->
				<select class="form-control" id="religion" name="religion">
					<option value="">All religions</option>
					<option value="ISLAM" <?= $accessories->religion == "ISLAM" ? "selected" : ""; ?>>ISLAM</option>
					<option value="BUDDHISM" <?= $accessories->religion == "BUDDHISM" ? "selected" : ""; ?>>BUDDHISM</option>
					<option value="HINDU" <?= $accessories->religion == "HINDU" ? "selected" : ""; ?>>HINDU</option>
					<option value="CHRISTIANITY" <?= $accessories->religion == "CHRISTIANITY" ? "selected" : ""; ?>>CHRISTIANITY</option>
					<option value="" <?= $accessories->religion != "" && $accessories->religion != "ISLAM" && $accessories->religion != "BUDDHISM" && $accessories->religion != "HINDU" && $accessories->religion != "CHRISTIANITY" ? "selected" : ""; ?>>OTHERS</option>
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
