<div class="form-group">
	<label class="label_">RANK</label><!--Pangkat-->
	<select class="form-control" id="pangkat" name="pangkat">
		<option value="">Choose rank</option>
		@foreach($pangkats as $pangkat)
			<option <?php if(!empty($personal_detail)) { if($personal_detail->pangkat == $pangkat->id) { echo "selected"; } } ?> value="{{ $pangkat->id }}">{{ $pangkat->value }}</option>
		@endforeach
	</select>
</div>