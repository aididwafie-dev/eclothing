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

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">

			<input type="hidden" name="uniform_id" value="{{ $data['uniforms']->id }}">

			<input type="hidden" name="id" value="{{ $data['clothes'][0]->id }}">
			@foreach($data['clothes'][0] as $key => $value)

			@if($key == 'clothes_type')
			<div class="col-sm-12">
				<label class="label_">Cloth:</label>
				<input class="form-control" type="text" id="{{ $key }}" name="{{ $key }}" value="{{ $value }}" required />
				<br />
			</div>
			@endif

			@if($key == 'clothes_size')
			<div class="col-sm-12">
				<label class="label_">Size:</label>
				<input class="form-control" type="text" id="{{ $key }}" name="{{ $key }}" value="{{ $value }}" required />
				<br />
			</div>
			@endif
			@endforeach
			<div class="form-group col-sm-6">
				<label class="label_">Gender:</label>
				<!--Jantina--><br />
				<select multiple id="jantina" name="jantina[]">
					@foreach($jantinas as $jantina)
					<option value="{{ $jantina->id }}" <?php if (in_array($jantina->id, explode(",", $data['clothes'][0]->jantina))) { ?>selected<?php }?>>{{ $jantina->value }}</option>
					@endforeach
				</select>
				<br />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Rank:</label>
				<!--Jantina--><br />
				<select multiple id="pangkat" name="pangkat[]">
					@foreach($pangkats as $pangkat)
					<option value="{{ $pangkat->id }}" <?php if (in_array($pangkat->id, explode(",", $data['clothes'][0]->pangkat))) { ?>selected<?php }?>>{{ $pangkat->value }}</option>
					@endforeach
				</select>
				<br />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Tred:</label>
				<!--Jantina--><br />
				<select multiple id="ketukangan" name="ketukangan[]">
					@foreach($ketukangans as $ketukangan)
					<option value="{{ $ketukangan->id }}" <?php if (in_array($ketukangan->id, explode(",", $data['clothes'][0]->ketukangan))) { ?>selected<?php }?>>{{ $ketukangan->value }} - {{ $ketukangan->officer_recruit == 1 ? "OFFICER" : ($ketukangan->officer_recruit == 2 ? "RECRUIT" : "BOTH") }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Religion:</label>
				<!--Status Religion--><br />
				<select multiple id="religion" name="religion[]">
					<option value="ISLAM" <?= in_array("ISLAM", explode(",", $data['clothes'][0]->religion)) ? "selected" : ""; ?>>ISLAM</option>
					<option value="BUDDHISM" <?= in_array("BUDDHISM", explode(",", $data['clothes'][0]->religion)) ? "selected" : ""; ?>>BUDDHISM</option>
					<option value="HINDU" <?= in_array("HINDU", explode(",", $data['clothes'][0]->religion)) ? "selected" : ""; ?>>HINDU</option>
					<option value="CHRISTIANITY" <?= in_array("CHRISTIANITY", explode(",", $data['clothes'][0]->religion)) ? "selected" : ""; ?>>CHRISTIANITY</option>
				</select>
			</div>
			
			<div class="col-sm-12">Leave the four boxes blank to not filter and show to all.</div>
			<?php
										$stringId = "DCS".$data['uniforms']->id."DCS";
										$uniformId = base64_encode($stringId);
									?>
			<div class="text-center"><br />
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
