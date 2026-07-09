@include('static-layout/header')
@include('static-layout/sidebar')

</br>
<div class="title"><i class="fa fa-address-card" aria-hidden="true"></i> Personal Details</div>
<hr>
<div class="containerMain">
	<div class="content">
		<div class="title"><i class="fa fa-address-book" aria-hidden="true"></i> Provide Personal Details:</div>
		<hr>

		<form autocomplete="off" method="post" action="{{ url('/personal-details-save') }}" name="save-details" id="save-details">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">


			<div class="form-group col-sm-6">
				<label class="label_">SERVICE ID</label>
				<input class="form-control" type="text" id="s_id" name="s_id" value="<?php echo $data['personal_data']['service_id']; ?>" readonly />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">NAME</label>
				<input class="form-control" type="text" id="name" name="name" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->name; } ?>" placeholder="Enter your name...." />
			</div>

			<div class="form-group col-sm-6" id="service_dropdown">
				<label class="label_">SERVICE</label>
				<!--Piliih Angkatan-->
				<select class="form-control" id="piliih_angkatan" name="piliih_angkatan">
					<option value="">Choose service</option>
					@foreach($data['personal_data']['dropdown_data']['piliih_angkatans'] as $piliih_angkatans)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->piliih_angkatan == $piliih_angkatans->id) { echo "selected"; } } ?> value="{{ $piliih_angkatans->id }}">{{ $piliih_angkatans->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<!--<label  class="label_">Ketukangan Type</label><br>-->
				<label class="radio-inline">
					<input type="radio" id="officer" <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan_type == 1) { echo "checked"; } }else{ echo "checked"; } ?> name="ketukangans_type" value="1" /><label class="label_ text-uppercase">Officer</label>
				</label>
				<label class="radio-inline">
					<input type="radio" id="recruit" <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan_type == 2) { echo "checked"; } } ?> name="ketukangans_type" value="2" /><label class="label_ text-uppercase">Other Rank</label>
					<!--Recruit-->
				</label>

				<div id="rankLoader" style="color: #007acc">
					<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
					<span style="font-size: 125%">Loading rank values....</span>
				</div>
				<div id="rank_dropdown"></div>
			</div>

			<div class="form-group col-sm-6" id="officer_drop">
				<label class="label_">TRED</label>
				<!--Ketukangan-->
				<select class="form-control" id="ketukangans_officer" name="ketukangans_officer">
					<option value="">Choose tred</option>
					@foreach($data['personal_data']['dropdown_data']['ketukangans_officer'] as $ketukangans_officer)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_officer->id) { echo "selected"; } } ?> value="{{ $ketukangans_officer->id }}">{{ $ketukangans_officer->value }}</option>
					@endforeach
					@foreach($data['personal_data']['dropdown_data']['ketukangans_both'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6" id="recruit_drop">
				<label class="label_">TRED</label>
				<!--Ketukangan-->
				<select class="form-control" id="ketukangans_recruit" name="ketukangans_recruit">
					<option value="">Choose tred</option>
					@foreach($data['personal_data']['dropdown_data']['ketukangans_recruit'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach
					@foreach($data['personal_data']['dropdown_data']['ketukangans_both'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach

				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">UNIT</label>
				<select class="form-control" id="unit" name="unit">
					<option value="">Choose unit</option>
					@foreach($data['personal_data']['dropdown_data']['units'] as $units)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->unit == $units->id) { echo "selected"; } } ?> value="{{ $units->id }}">{{ $units->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">GENDER</label>
				<!--Jantina-->
				<select class="form-control" id="jantina" name="jantina">
					<option value="">Choose gender</option>
					@foreach($data['personal_data']['dropdown_data']['jantinas'] as $jantinas)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->jantina == $jantinas->id) { echo "selected"; } } ?> value="{{ $jantinas->id }}">{{ $jantinas->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">TELEPHONE NUMBER</label>
				<input class="form-control" type="text" id="telephone_number" name="telephone_number" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->telephone_number; } ?>" placeholder="Enter your telephone number...." />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">DUTY STATUS</label>
				<!--Status Penggunaan-->
				<select class="form-control" id="status_penggunaan" name="status_penggunaan">
					<option value="">Select duty status</option>
					@foreach($data['personal_data']['dropdown_data']['status_penggunaans'] as $status_penggunaans)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->status_penggunaan == $status_penggunaans->id) { echo "selected"; } } ?> value="{{ $status_penggunaans->id }}">{{ $status_penggunaans->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-3">
				<label class="label_">RELIGION</label>
				<!--Status Religion-->
				<select class="form-control" id="status_religion" name="status_religion">
					<option value="ISLAM" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "ISLAM" ? "selected" : ""; ?>>ISLAM</option>
					<option value="BUDDHISM" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "BUDDHISM" ? "selected" : ""; ?>>BUDDHISM</option>
					<option value="HINDU" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "HINDU" ? "selected" : ""; ?>>HINDU</option>
					<option value="CHRISTIANITY" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "CHRISTIANITY" ? "selected" : ""; ?>>CHRISTIANITY</option>
					<option value="" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion != "" && $data['personal_data']['personal_detail']->religion != "ISLAM" && $data['personal_data']['personal_detail']->religion != "BUDDHISM" && $data['personal_data']['personal_detail']->religion != "HINDU" && $data['personal_data']['personal_detail']->religion != "CHRISTIANITY" ? "selected" : ""; ?>>OTHERS</option>
				</select>
			</div>
			<div class="form-group col-sm-3">
				<label class="label_"> &#160; </label>
				<!--Status Religion-->
				<input class="form-control <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion != "" && $data['personal_data']['personal_detail']->religion != "ISLAM" && $data['personal_data']['personal_detail']->religion != "BUDDHISM" && $data['personal_data']['personal_detail']->religion != "HINDU" && $data['personal_data']['personal_detail']->religion != "CHRISTIANITY" ? "" : "hide"; ?>" id="other_religion" value="<?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion != "" && $data['personal_data']['personal_detail']->religion != "ISLAM" && $data['personal_data']['personal_detail']->religion != "BUDDHISM" && $data['personal_data']['personal_detail']->religion != "HINDU" && $data['personal_data']['personal_detail']->religion != "CHRISTIANITY" ? $data['personal_data']['personal_detail']->religion : ""; ?>" name="status_religion_others" placeholder="Religion name" />
			</div>


			<?php
										if(!empty($data['personal_data']['personal_detail'])) {
											$address = explode("|", $data['personal_data']['personal_detail']->address);
											$line1 = $address[0];
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
				<label class="label_">ADDRESS</label>
				<input class="form-control" id="address1" name="address1" value="<?php if(!empty($line1)) { echo $line1; } ?>" placeholder="Address line 1" />
			</div>
			<div class="form-group col-sm-12">
				<input class="form-control" id="address2" name="address2" value="<?php if(!empty($city)) { echo $city; } ?>" placeholder="City" />
			</div>
			<div class="form-group col-sm-6">

				<?php $states = ["JOHOR", "W.P. KUALA LUMPUR", "W.P. LABUAN", "W.P. PUTRAJAYA", "KEDAH", "KELANTAN", "MELAKA", "NEGERI SEMBILAN", "PAHANG", "PERAK", "PERLIS", "PULAU PINANG", "SABAH", "SARAWAK", "SELANGOR", "TERENGGANU", "OTHERS"]; ?>
				<select class="form-control" id="address3" name="address3">
					<option value="">--SELECT STATE--</option>
					<?php	foreach ($states as $state_val) { ?>
					<option value="{{$state_val}}" <?= (isset($state) && $state == $state_val ? 'selected' : ''); ?>>{{$state_val}}</option>
					<?php } ?>
				</select>
			</div>
			<div class="form-group col-sm-6">
				<input class="form-control" id="address4" name="address4" value="<?php if(!empty($postcode)) { echo $postcode; } ?>" placeholder="Postcode" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">NEXT OF KIN NAME</label>
				<!--Nama Waris-->
				<input class="form-control" type="text" id="nama_waris" name="nama_waris" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->nama_waris; } ?>" placeholder="Enter your next of kin name" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">NEXT OF KIN CONTACT NUMBER</label>
				<!--Telephone Number Waris-->
				<input class="form-control" type="text" id="tele_number_waris" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->telephone_number_waris; } ?>" name="tele_number_waris" placeholder="Enter your next of kin contact number" />
			</div>


			<div class="form-group col-sm-6">
				<label class="label_">NAME ON UNIFORM TAG</label>
				<!--Nama Waris-->
				<input class="form-control" type="text" id="name_tag" name="name_tag" value="<?php if(!empty($data['personal_data']['personal_detail']) && !empty($data['personal_data']['personal_detail']->name_tag)) { echo $data['personal_data']['personal_detail']->name_tag; } ?>" placeholder="Enter the name to be printed on your uniform tag" required maxlength="8" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">OLD UNIT</label>
				<!--Unit Lama-->

				<select class="form-control" id="unit_lama" name="unit_lama">
					<option value="">Choose old unit</option>
					@foreach($data['personal_data']['dropdown_data']['units'] as $units)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->unit_lama == $units->value) { echo "selected"; } } ?> value="{{ $units->value }}">{{ $units->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">POSTING AUTHORITY</label>
				<!--Kem Lama-->
				<input class="form-control" type="text" id="kem_lama" name="kem_lama" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->kem_lama; } ?>" placeholder="Enter your posting authority" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">SPECIAL AUTHORITY (IF AVAILABLE)</label>
				<!--Spl Lama-->
				<input class="form-control" type="text" id="spl_lama" name="spl_lama" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->spl_lama; } ?>" placeholder="Enter your special authority" />
			</div>

			<div class="clearfix"></div>
			<div class="subBtn text-center">
				<input class="btn btn-info" type="submit" value="SAVE" id="submit" name="submit" />
				<a href="{{ url('/personal-details/restore') }}" class="btn btn-default"> Restore</a>
			</div>
		</form>
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
	}, "Capital Letters only please");
	jQuery.validator.addMethod("lettersonly", function(value, element) {
		return this.optional(element) || /^[A-Za-z\s]+$/.test(value);
	}, "Letters only please");
	$("#save-details").validate({
		rules: {
			name: {
				required: true,
				lettersonly: true,
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
				lettersonly: true,
			},
			tele_number_waris: {
				required: true,
				maxlength: 11,
				number: true,
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
			},
		},
		messages: {
			name: "Enter your full name",
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
			address4: "Choose your postalcode",
			nama_waris: "Enter a valid next of kin name",
			tele_number_waris: "Enter a valid next of kin contact number",
			status_penggunaan: "Select your duty status",
			unit_lama: "Choose your correct old unit",
			kem_lama: "Choose your correct posting authority",
		},

		submitHandler: function(form) {
			form.submit();
		}
	});
	$(document).ready(function() {
		$("#officer_drop").hide();
		$("#recruit_drop").hide();
		var radio = '<?php if(!empty($data['personal_data']['personal_detail']))
					{
						echo $data['personal_data']['personal_detail']->ketukangan_type;
					}
				?>';
		if (radio == 1) {
			$("#officer_drop").show();
		} else if (radio == 2) {
			$("#recruit_drop").show();
		} else {
			$("#officer_drop").show();
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

		$("#rankLoader").hide();
		var exist = '<?php if(!empty($data['personal_data']['personal_detail'])){ echo 'true'; } ?>';
		if (exist == 'true') {
			ajaxLoadRankDropdownValues();
		}
		$("#piliih_angkatan").change(function() {
			ajaxLoadRankDropdownValues();
		});
		$('#name_tag').keyup(function() {
			this.value = this.value.toLocaleUpperCase();
		})
		$("input[name=ketukangans_type]:radio").change(function() {
			ajaxLoadRankDropdownValues();
		});

		function ajaxLoadRankDropdownValues() {
			var serviceId = $("#piliih_angkatan").val();
			if (serviceId != '') {
				var tredType = $("input[type='radio']:checked").val();
				$('#rank_dropdown').html('');
				$("#rankLoader").show();
				if (serviceId) {
					$.ajaxSetup({
						headers: {
							'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
						}
					});
					$.ajax({
						type: 'post',
						url: '/ajax-load-rank-values',
						data: {
							'serviceId': serviceId,
							'tredType': tredType
						},
						success: function(result) {
							$("#rankLoader").hide();
							$('#rank_dropdown').html(result);
						}
					});
				}
			} else {
				$('#rank_dropdown').html('');
			}
		}
	});

</script>
</body>

</html>
