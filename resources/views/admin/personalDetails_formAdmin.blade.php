@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-address-card" aria-hidden="true"></i> Users Personal Details</div>
<hr>
<div class="containerMain">
	<div class="content">
		<div class="title"><i class="fa fa-pencil" aria-hidden="true"></i> Edit Users Personal Details:</div>
		<hr>
		@if(empty($data['personal_detail']))
		This User have not filled his personal details yet.
		@else
		<form autocomplete="off" method="post" action="{{ url('/change-personalDetails') }}" name="save-details" id="save-details">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
			<input type="hidden" name="user_id" id="user_id" value="{{ $data['personal_detail']->user_id }}">

			<div class="form-group">
				<label class="label_">SERVICE ID</label>
				<input class="form-control" type="text" id="s_id" name="s_id" value="<?php echo $data['personal_detail']->s_id; ?>" readonly />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">NAME</label>
				<input class="form-control" type="text" id="name" name="name" value="<?php if(!empty($data['personal_detail'])) { echo $data['personal_detail']->name; } ?>" placeholder="Enter your name...." />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">SERVICES</label>
				<!--Piliih Angkatan-->
				<select class="form-control" id="piliih_angkatan" name="piliih_angkatan">
					<option value="">Choose service</option>
					@foreach($data['dropdown_data']['piliih_angkatans'] as $piliih_angkatans)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->piliih_angkatan == $piliih_angkatans->id) { echo "selected"; } } ?> value="{{ $piliih_angkatans->id }}">{{ $piliih_angkatans->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-12">
				<label class="radio-inline">
					<input type="radio" id="officer" <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->ketukangan_type == 1) { echo "checked"; } } ?> name="ketukangans_type" value="1" />Officer
				</label>
				<label class="radio-inline">
					<input type="radio" id="recruit" <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->ketukangan_type == 2) { echo "checked"; } } ?> name="ketukangans_type" value="2" />Other Rank
				</label>
			</div>

			<div id="rank_dropdown"></div>

			<div class="form-group col-sm-6">
				<label class="label_">JAWATAN (POSITION)</label>
				<input class="form-control" type="text" id="position" name="position" value="<?php echo isset($data['user_position']) ? $data['user_position'] : ''; ?>" placeholder="e.g. PEGAWAI TADBIR GRED W29" maxlength="255" />
			</div>

			<div class="form-group col-sm-6" id="officer_drop">
				<label class="label_">TRED</label>
				<!--Ketukangan-->
				<select class="form-control" id="ketukangans_officer" name="ketukangans_officer">
					<option value="">Choose tred</option>
					@foreach($data['dropdown_data']['ketukangans_officer'] as $ketukangans_officer)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->ketukangan == $ketukangans_officer->id) { echo "selected"; } } ?> value="{{ $ketukangans_officer->id }}">{{ $ketukangans_officer->value }}</option>
					@endforeach
					@foreach($data['dropdown_data']['ketukangans_both'] as $ketukangans_officer)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->ketukangan == $ketukangans_officer->id) { echo "selected"; } } ?> value="{{ $ketukangans_officer->id }}">{{ $ketukangans_officer->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6" id="recruit_drop">
				<label class="label_">TRED</label>
				<!--Ketukangan-->
				<select class="form-control" id="ketukangans_recruit" name="ketukangans_recruit">
					<option value="">Choose tred</option>
					@foreach($data['dropdown_data']['ketukangans_recruit'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach
					@foreach($data['dropdown_data']['ketukangans_both'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Unit</label>
				<select class="form-control" id="unit" name="unit">
					<option value="">Choose unit</option>
					@foreach($data['dropdown_data']['units'] as $units)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->unit == $units->id) { echo "selected"; } } ?> value="{{ $units->id }}">{{ $units->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">GENDER</label>
				<!--Jantina-->
				<select class="form-control" id="jantina" name="jantina">
					<option value="">Choose gender</option>
					@foreach($data['dropdown_data']['jantinas'] as $jantinas)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->jantina == $jantinas->id) { echo "selected"; } } ?> value="{{ $jantinas->id }}">{{ $jantinas->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Telephone Number</label>
				<input class="form-control" type="text" id="telephone_number" name="telephone_number" value="<?php if(!empty($data['personal_detail'])) { echo $data['personal_detail']->telephone_number; } ?>" placeholder="Enter your telephone number...." />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Duty Status</label>
				<!--Status Penggunaan-->
				<select class="form-control" id="status_penggunaan" name="status_penggunaan">
					<option value="">Select duty status</option>
					@foreach($data['dropdown_data']['status_penggunaans'] as $status_penggunaans)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->status_penggunaan == $status_penggunaans->id) { echo "selected"; } } ?> value="{{ $status_penggunaans->id }}">{{ $status_penggunaans->value }}</option>
					@endforeach
				</select>
			</div>

		
			<div class="form-group col-sm-3">
				<label class="label_">RELIGION</label>
				<!--Status Religion-->
				<select class="form-control" id="status_religion" name="status_religion">
					<option value="ISLAM" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "ISLAM" ? "selected" : ""; ?>>ISLAM</option>
					<option value="BUDDHISM" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "BUDDHISM" ? "selected" : ""; ?>>BUDDHISM</option>
					<option value="HINDU" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "HINDU" ? "selected" : ""; ?>>HINDU</option>
					<option value="CHRISTIANITY" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion == "CHRISTIANITY" ? "selected" : ""; ?>>CHRISTIANITY</option>
					<option value="" <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion != "" && $data['personal_detail']->religion != "ISLAM" && $data['personal_detail']->religion != "BUDDHISM" && $data['personal_detail']->religion != "HINDU" && $data['personal_detail']->religion != "CHRISTIANITY" ? "selected" : ""; ?>>OTHERS</option>
				</select>
			</div>
			<div class="form-group col-sm-3">
				<label class="label_"> &#160; </label>
				<!--Status Religion-->
				<input class="form-control <?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion != "" && $data['personal_detail']->religion != "ISLAM" && $data['personal_detail']->religion != "BUDDHISM" && $data['personal_detail']->religion != "HINDU" && $data['personal_detail']->religion != "CHRISTIANITY" ? "" : "hide"; ?>" id="other_religion" value="<?= !empty($data['personal_detail']) && $data['personal_detail']->religion && $data['personal_detail']->religion != "" && $data['personal_detail']->religion != "ISLAM" && $data['personal_detail']->religion != "BUDDHISM" && $data['personal_detail']->religion != "HINDU" && $data['personal_detail']->religion != "CHRISTIANITY" ? $data['personal_detail']->religion : ""; ?>" name="status_religion_others" placeholder="Religion name" />
			</div>
			
			<?php
										if(!empty($data['personal_detail'])) {
											$address = explode("|", $data['personal_detail']->address);
											if (isset($address[0])) {
												$line1 = $address[0];
											}
											if (isset($address[1])) {
												$city = $address[1];
											}
											if (isset($address[2])) {
												$state = $address[2];
											}
											if (isset($address[3])) {
												$postcode = $address[3];
											}
										}
									?>

			<div class="form-group col-sm-12">
				<label class="label_">Address</label>
				<input class="form-control" id="address1" name="address1" value="<?php if(!empty($line1)) { echo $line1; } ?>" placeholder="Address line1...." />
			</div>
			<div class="form-group col-sm-12">
				<input class="form-control" id="address2" name="address2" value="<?php if(!empty($city)) { echo $city; } ?>" placeholder="City...." />
			</div>
			<div class="form-group col-sm-6">

				<?php $states = ["JOHOR", "W.P. KUALA LUMPUR", "W.P. LABUAN", "W.P. PUTRAJAYA", "KEDAH", "KELANTAN", "MELAKA", "NEGERI SEMBILAN", "PAHANG", "PERAK", "PERLIS", "PULAU PINANG", "SABAH", "SARAWAK", "SELANGOR", "TERENGGANU", "OTHERS"]; ?>
				<select class="form-control" id="address3" name="address3">
					<option value="">--SELECT STATE--</option>
					<?php	foreach ($states as $state_val) { ?>
					<option value="{{$state_val}}" <?= ($state == $state_val ? 'selected' : ''); ?>>{{$state_val}}</option>
					<?php } ?>
				</select>
			</div>
			<div class="form-group col-sm-6">
				<input class="form-control" id="address4" name="address4" value="<?php if(!empty($postcode)) { echo $postcode; } ?>" placeholder="Postcode...." />
			</div>

			<!-- <div class="form-group">
										<label class="label_">Address</label>
										<textarea class="form-control" id="address" name="address" placeholder="Enter your address...."><?php //if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->address; } ?></textarea>
									</div> -->

			<div class="form-group col-sm-6">
				<label class="label_">Next of Kin name</label>
				<!--Nama Waris-->
				<input class="form-control" type="text" id="nama_waris" name="nama_waris" value="<?php if(!empty($data['personal_detail'])) { echo $data['personal_detail']->nama_waris; } ?>" placeholder="Enter your next of kin name" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">Next of Kin Contact Number</label>
				<!--Telephone Number Waris-->
				<input class="form-control" type="text" id="tele_number_waris" value="<?php if(!empty($data['personal_detail'])) { echo $data['personal_detail']->telephone_number_waris; } ?>" name="tele_number_waris" placeholder="Enter your next of kin contact number" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">NAME ON UNIFORM TAG</label>
				<!--Kem Lama-->
				<input class="form-control" type="text" id="name_tag" name="name_tag" value="<?php if(!empty($data['personal_detail'])) { echo $data['personal_detail']->name_tag; } ?>" placeholder="Enter your uniform tag" maxlength="8" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">OLD UNIT</label>
				<!--Unit Lama-->

				<select class="form-control" id="unit_lama" name="unit_lama">
					<option value="">Choose old unit</option>
					@foreach($data['dropdown_data']['units'] as $units)
					<option <?php if(!empty($data['personal_detail'])) { if($data['personal_detail']->unit_lama == $units->value) { echo "selected"; } } ?> value="{{ $units->value }}">{{ $units->value }}</option>
					@endforeach
				</select>

			</div>

			<div class="form-group col-sm-6">
				<label class="label_">POSTING AUTHORITY</label>
				<!--Kem Lama-->
				<input class="form-control" type="text" id="kem_lama" name="kem_lama" value="<?php if(!empty($data['personal_detail'])) { echo $data['personal_detail']->kem_lama; } ?>" placeholder="Enter your posting authority" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">SPECIAL AUTHORITY (IF AVAILABLE)</label>
				<!--Kem Lama-->
				<input class="form-control" type="text" id="spl_lama" name="spl_lama" value="<?php if(!empty($data['personal_detail'])) { echo $data['personal_detail']->spl_lama; } ?>" placeholder="Enter your special authority" />
			</div>

			<div class="subBtn col-sm-12">
				<input class="btn btn-default" type="submit" value="SAVE" id="submit" name="submit" />
				<a href="{{ url('/admin-cancel') }}" class="btn btn-default"> CANCEL</a>
			</div>

			<div class="clearfix"></div>
		</form>
		@endif
	</div>
</div>

<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
<style type="text/css">
	label.error {
		color: red;
	}

	input.error {
		border: 1px solid red;
	}

</style>
<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js"></script>
<script type="text/javascript">
	$('#name').keyup(function() {
		this.value = this.value.toLocaleUpperCase();
	});
	$('#nama_waris').keyup(function() {
		this.value = this.value.toLocaleUpperCase();
	});
	$('#address1').keyup(function() {
		this.value = this.value.toLocaleUpperCase();
	});
	$('#name_tag').keyup(function() {
		this.value = this.value.toLocaleUpperCase();
	})
	$('#address2').keyup(function() {
		this.value = this.value.toLocaleUpperCase();
	});
	$('#address3').keyup(function() {
		this.value = this.value.toLocaleUpperCase();
	});
	$('#other_religion').keyup(function() {
		this.value = this.value.toLocaleUpperCase();
	});

	jQuery.validator.addMethod("Caplettersonly", function(value, element) {
		return this.optional(element) || /^[A-Z\s]+$/.test(value);
	}, "Letters only please");
	jQuery.validator.addMethod("lettersonly", function(value, element) {
		return this.optional(element) || /^[A-Za-z\s]+$/.test(value);
	}, "Letters only please");
	$("#save-details").validate({
		rules: {
			name: {
				required: true,
				//Caplettersonly: true,
			},
			piliih_angkatan: {
				required: true,
			},
			pangkat: {
				required: true,
			},
			ketukangans_type: {
				required: true,
			},
			ketukangans_officer: {
				required: true,
			},
			ketukangans_recruit: {
				required: true,
			},
			unit: {
				required: true,
			},
			jantina: {
				required: true,
			},
			telephone_number: {
				required: true,
				maxlength: 11,
				number: true,
			},
			address1: {
				required: true,
			},
			address2: {
				required: true,
			},
			address3: {
				required: true,
			},
			address4: {
				required: true,
				number: true,
			},
			nama_waris: {
				required: true,
			},
			tele_number_waris: {
				required: true
			},
			status_penggunaan: {
				required: true,
			},
			unit_lama: {
				required: true
			},
			kem_lama: {
				required: true,
				maxlength: 250,
			}
		},
		messages: {
			name: "Put your name in caps",
			piliih_angkatan: "Choose your service",
			pangkat: "Choose your rank",
			ketukangans_type: "Choose tred type",
			ketukangans_officer: "Choose your tred",
			ketukangans_recruit: "Choose your tred",
			unit: "Choose your unit",
			jantina: "Choose your gender",
			telephone_number: "Choose your correct telephone number",
			address1: "Choose your Address",
			address2: "Choose your city",
			address3: "Choose your state",
			address4: "Choose your postcode",
			nama_waris: "Enter a valid next of kin name",
			tele_number_waris: "Enter a valid next of kin contact number",
			status_penggunaan: "Select your duty status",
			unit_lama: "Choose your correct old unit",
			kem_lama: "Choose your correct posting authority",
		},

		submitHandler: function(form) {
			// do other things for a valid form
			form.submit();
		}
	});
	$(document).ready(function() {
		$("#officer_drop").hide();
		$("#recruit_drop").hide();
		var radio = '<?php if(!empty($data['personal_detail']))
					{
						echo $data['personal_detail']->ketukangan_type;
					}
				?>';
		if (radio == 1) {
			$("#officer_drop").show();
		} else if (radio == 2) {
			$("#recruit_drop").show();
		} else {
			$("#officer_drop").hide();
			$("#recruit_drop").hide();
		}
		
		$("#status_religion").change(function() {
			if (!$(this).val()) {
				$("#other_religion").removeClass("hide");
				$("#other_religion").attr("required", "required");
				$("#other_religion").focus();
			} else {
				$("#other_religion").addClass("hide");
				$("#other_religion").removeAttr("required");
				$("#other_religion").val("");
			}
		})
		$("#officer").click(function() {
			$("#officer_drop").fadeIn();
			$("#recruit_drop").fadeOut();
		});

		$("#recruit").click(function() {
			$("#recruit_drop").fadeIn();
			$("#officer_drop").fadeOut();
		});

		var exist = '<?php if(!empty($data['personal_detail'])){ echo 'true'; } ?>';
		if (exist == 'true') {
			ajaxLoadRankDropdownValues();
		}
		$("#piliih_angkatan").change(function() {
			ajaxLoadRankDropdownValues();
		});
		$("input[name=ketukangans_type]:radio").change(function() {
			ajaxLoadRankDropdownValues();
		});

		function ajaxLoadRankDropdownValues() {
			var serviceId = $("#piliih_angkatan").val();
			var tredType = $("input[type='radio']:checked").val();
			var userId = $("#user_id").val();
			$('#rank_dropdown').html('');
			//$('#loader_image').show();
			if (serviceId) {
				$.ajaxSetup({
					headers: {
						'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
					}
				});
				$.ajax({
					type: 'post',
					url: '/admin/ajax-load-rank-values',
					data: {
						'serviceId': serviceId,
						'tredType': tredType,
						'userId': userId
					},
					success: function(result) {
						//$('#loader_image').fadeOut('slow');
						$('#rank_dropdown').html(result);
						//alert(result);
					}
				});
			}
		}
	});

</script>
</body>

</html>
