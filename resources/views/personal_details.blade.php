@include('static-layout/header')
@include('static-layout/sidebar')

</br>
<div class="title"><i class="fa fa-address-card" aria-hidden="true"></i> {{ __('app.profile.title') }}</div>
<hr>
<div class="containerMain">
	<div class="content">
		<div class="title"><i class="fa fa-address-book" aria-hidden="true"></i> {{ __('app.profile.subtitle') }}</div>
		<hr>

		<form autocomplete="off" method="post" action="{{ url('/personal-details-save') }}" name="save-details" id="save-details">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">


			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.service_id') }}</label>
				<input class="form-control" type="text" id="s_id" name="s_id" value="<?php echo $data['personal_data']['service_id']; ?>" readonly />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.name') }}</label>
				<input class="form-control" type="text" id="name" name="name" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->name; } ?>" placeholder="{{ __('app.profile.name_placeholder') }}" />
			</div>

			<div class="form-group col-sm-6" id="service_dropdown">
				<label class="label_">{{ __('app.profile.service') }}</label>
				<!--Piliih Angkatan-->
				<select class="form-control" id="piliih_angkatan" name="piliih_angkatan">
					<option value="">{{ __('app.profile.choose_service') }}</option>
					@foreach($data['personal_data']['dropdown_data']['piliih_angkatans'] as $piliih_angkatans)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->piliih_angkatan == $piliih_angkatans->id) { echo "selected"; } } ?> value="{{ $piliih_angkatans->id }}">{{ $piliih_angkatans->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<!--<label  class="label_">Ketukangan Type</label><br>-->
				<label class="radio-inline">
					<input type="radio" id="officer" <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan_type == 1) { echo "checked"; } }else{ echo "checked"; } ?> name="ketukangans_type" value="1" /><label class="label_ text-uppercase">{{ __('app.profile.officer') }}</label>
				</label>
				<label class="radio-inline">
					<input type="radio" id="recruit" <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan_type == 2) { echo "checked"; } } ?> name="ketukangans_type" value="2" /><label class="label_ text-uppercase">{{ __('app.profile.other_rank') }}</label>
					<!--Recruit-->
				</label>

				<div id="rankLoader" style="color: var(--brand)">
					<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
					<span style="font-size: 125%">{{ __('app.profile.loading_ranks') }}</span>
				</div>
				<div id="rank_dropdown"></div>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.position') }}</label>
				<input class="form-control" type="text" id="position" name="position" value="<?php echo isset($data['personal_data']['user_position']) ? $data['personal_data']['user_position'] : ''; ?>" placeholder="{{ __('app.profile.position_placeholder') }}" maxlength="255" />
			</div>

			<div class="form-group col-sm-6" id="officer_drop">
				<label class="label_">{{ __('app.profile.tred') }}</label>
				<!--Ketukangan-->
				<select class="form-control" id="ketukangans_officer" name="ketukangans_officer">
					<option value="">{{ __('app.profile.choose_tred') }}</option>
					@foreach($data['personal_data']['dropdown_data']['ketukangans_officer'] as $ketukangans_officer)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_officer->id) { echo "selected"; } } ?> value="{{ $ketukangans_officer->id }}">{{ $ketukangans_officer->value }}</option>
					@endforeach
					@foreach($data['personal_data']['dropdown_data']['ketukangans_both'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6" id="recruit_drop">
				<label class="label_">{{ __('app.profile.tred') }}</label>
				<!--Ketukangan-->
				<select class="form-control" id="ketukangans_recruit" name="ketukangans_recruit">
					<option value="">{{ __('app.profile.choose_tred') }}</option>
					@foreach($data['personal_data']['dropdown_data']['ketukangans_recruit'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach
					@foreach($data['personal_data']['dropdown_data']['ketukangans_both'] as $ketukangans_recruit)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->ketukangan == $ketukangans_recruit->id) { echo "selected"; } } ?> value="{{ $ketukangans_recruit->id }}">{{ $ketukangans_recruit->value }}</option>
					@endforeach

				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.unit') }}</label>
				<select class="form-control" id="unit" name="unit">
					<option value="">{{ __('app.profile.choose_unit') }}</option>
					@foreach($data['personal_data']['dropdown_data']['units'] as $units)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->unit == $units->id) { echo "selected"; } } ?> value="{{ $units->id }}">{{ $units->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.gender') }}</label>
				<!--Jantina-->
				<select class="form-control" id="jantina" name="jantina">
					<option value="">{{ __('app.profile.choose_gender') }}</option>
					@foreach($data['personal_data']['dropdown_data']['jantinas'] as $jantinas)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->jantina == $jantinas->id) { echo "selected"; } } ?> value="{{ $jantinas->id }}">{{ $jantinas->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.telephone') }}</label>
				<input class="form-control" type="text" id="telephone_number" name="telephone_number" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->telephone_number; } ?>" placeholder="{{ __('app.profile.telephone_placeholder') }}" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.duty_status') }}</label>
				<!--Status Penggunaan-->
				<select class="form-control" id="status_penggunaan" name="status_penggunaan">
					<option value="">{{ __('app.profile.choose_duty_status') }}</option>
					@foreach($data['personal_data']['dropdown_data']['status_penggunaans'] as $status_penggunaans)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->status_penggunaan == $status_penggunaans->id) { echo "selected"; } } ?> value="{{ $status_penggunaans->id }}">{{ $status_penggunaans->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-3">
				<label class="label_">{{ __('app.profile.religion') }}</label>
				<!--Status Religion-->
				<select class="form-control" id="status_religion" name="status_religion">
					<option value="ISLAM" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "ISLAM" ? "selected" : ""; ?>>{{ __('app.profile.religion_islam') }}</option>
					<option value="BUDDHISM" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "BUDDHISM" ? "selected" : ""; ?>>{{ __('app.profile.religion_buddhism') }}</option>
					<option value="HINDU" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "HINDU" ? "selected" : ""; ?>>{{ __('app.profile.religion_hindu') }}</option>
					<option value="CHRISTIANITY" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion == "CHRISTIANITY" ? "selected" : ""; ?>>{{ __('app.profile.religion_christianity') }}</option>
					<option value="" <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion != "" && $data['personal_data']['personal_detail']->religion != "ISLAM" && $data['personal_data']['personal_detail']->religion != "BUDDHISM" && $data['personal_data']['personal_detail']->religion != "HINDU" && $data['personal_data']['personal_detail']->religion != "CHRISTIANITY" ? "selected" : ""; ?>>{{ __('app.profile.religion_others') }}</option>
				</select>
			</div>
			<div class="form-group col-sm-3">
				<label class="label_"> &#160; </label>
				<!--Status Religion-->
				<input class="form-control <?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion != "" && $data['personal_data']['personal_detail']->religion != "ISLAM" && $data['personal_data']['personal_detail']->religion != "BUDDHISM" && $data['personal_data']['personal_detail']->religion != "HINDU" && $data['personal_data']['personal_detail']->religion != "CHRISTIANITY" ? "" : "hide"; ?>" id="other_religion" value="<?= !empty($data['personal_data']['personal_detail']) && $data['personal_data']['personal_detail']->religion && $data['personal_data']['personal_detail']->religion != "" && $data['personal_data']['personal_detail']->religion != "ISLAM" && $data['personal_data']['personal_detail']->religion != "BUDDHISM" && $data['personal_data']['personal_detail']->religion != "HINDU" && $data['personal_data']['personal_detail']->religion != "CHRISTIANITY" ? $data['personal_data']['personal_detail']->religion : ""; ?>" name="status_religion_others" placeholder="{{ __('app.profile.religion_other_placeholder') }}" />
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
				<label class="label_">{{ __('app.profile.address') }}</label>
				<input class="form-control" id="address1" name="address1" value="<?php if(!empty($line1)) { echo $line1; } ?>" placeholder="{{ __('app.profile.address1_placeholder') }}" />
			</div>
			<div class="form-group col-sm-12">
				<input class="form-control" id="address2" name="address2" value="<?php if(!empty($city)) { echo $city; } ?>" placeholder="{{ __('app.profile.city_placeholder') }}" />
			</div>
			<div class="form-group col-sm-6">

				<?php $states = ["JOHOR", "W.P. KUALA LUMPUR", "W.P. LABUAN", "W.P. PUTRAJAYA", "KEDAH", "KELANTAN", "MELAKA", "NEGERI SEMBILAN", "PAHANG", "PERAK", "PERLIS", "PULAU PINANG", "SABAH", "SARAWAK", "SELANGOR", "TERENGGANU", "OTHERS"]; ?>
				<select class="form-control" id="address3" name="address3">
					<option value="">{{ __('app.profile.select_state') }}</option>
					<?php	foreach ($states as $state_val) { ?>
					<option value="{{$state_val}}" <?= (isset($state) && $state == $state_val ? 'selected' : ''); ?>>{{$state_val}}</option>
					<?php } ?>
				</select>
			</div>
			<div class="form-group col-sm-6">
				<input class="form-control" id="address4" name="address4" value="<?php if(!empty($postcode)) { echo $postcode; } ?>" placeholder="{{ __('app.profile.postcode_placeholder') }}" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.kin_name') }}</label>
				<!--Nama Waris-->
				<input class="form-control" type="text" id="nama_waris" name="nama_waris" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->nama_waris; } ?>" placeholder="{{ __('app.profile.kin_name_placeholder') }}" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.kin_contact') }}</label>
				<!--Telephone Number Waris-->
				<input class="form-control" type="text" id="tele_number_waris" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->telephone_number_waris; } ?>" name="tele_number_waris" placeholder="{{ __('app.profile.kin_contact_placeholder') }}" />
			</div>


			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.name_tag') }}</label>
				<!--Nama Waris-->
				<input class="form-control" type="text" id="name_tag" name="name_tag" value="<?php if(!empty($data['personal_data']['personal_detail']) && !empty($data['personal_data']['personal_detail']->name_tag)) { echo $data['personal_data']['personal_detail']->name_tag; } ?>" placeholder="{{ __('app.profile.name_tag_placeholder') }}" required maxlength="8" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.old_unit') }}</label>
				<!--Unit Lama-->

				<select class="form-control" id="unit_lama" name="unit_lama">
					<option value="">{{ __('app.profile.choose_old_unit') }}</option>
					@foreach($data['personal_data']['dropdown_data']['units'] as $units)
					<option <?php if(!empty($data['personal_data']['personal_detail'])) { if($data['personal_data']['personal_detail']->unit_lama == $units->value) { echo "selected"; } } ?> value="{{ $units->value }}">{{ $units->value }}</option>
					@endforeach
				</select>
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.posting_authority') }}</label>
				<!--Kem Lama-->
				<input class="form-control" type="text" id="kem_lama" name="kem_lama" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->kem_lama; } ?>" placeholder="{{ __('app.profile.posting_authority_placeholder') }}" />
			</div>

			<div class="form-group col-sm-6">
				<label class="label_">{{ __('app.profile.special_authority') }}</label>
				<!--Spl Lama-->
				<input class="form-control" type="text" id="spl_lama" name="spl_lama" value="<?php if(!empty($data['personal_data']['personal_detail'])) { echo $data['personal_data']['personal_detail']->spl_lama; } ?>" placeholder="{{ __('app.profile.special_authority_placeholder') }}" />
			</div>

			<div class="clearfix"></div>
			<div class="subBtn text-center">
				<input class="btn btn-info" type="submit" value="{{ __('app.profile.save') }}" id="submit" name="submit" />
				<a href="{{ url('/personal-details/restore') }}" class="btn btn-default"> {{ __('app.profile.restore') }}</a>
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
	}, {!! json_encode(__('app.profile.caps_only')) !!});
	jQuery.validator.addMethod("lettersonly", function(value, element) {
		return this.optional(element) || /^[A-Za-z\s]+$/.test(value);
	}, {!! json_encode(__('app.profile.letters_only')) !!});
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
			name: {!! json_encode(__('app.profile.name_required')) !!},
			piliih_angkatan: {!! json_encode(__('app.profile.service_required')) !!},
			pangkat: {!! json_encode(__('app.profile.rank_required')) !!},
			ketukangans_type: {!! json_encode(__('app.profile.tred_type_required')) !!},
			ketukangans_officer: {!! json_encode(__('app.profile.tred_required')) !!},
			ketukangans_recruit: {!! json_encode(__('app.profile.tred_required')) !!},
			unit: {!! json_encode(__('app.profile.unit_required')) !!},
			jantina: {!! json_encode(__('app.profile.gender_required')) !!},
			telephone_number: {!! json_encode(__('app.profile.telephone_required')) !!},
			address1: {!! json_encode(__('app.profile.address_required')) !!},
			address2: {!! json_encode(__('app.profile.city_required')) !!},
			address3: {!! json_encode(__('app.profile.state_required')) !!},
			address4: {!! json_encode(__('app.profile.postcode_required')) !!},
			nama_waris: {!! json_encode(__('app.profile.kin_name_required')) !!},
			tele_number_waris: {!! json_encode(__('app.profile.kin_contact_required')) !!},
			status_penggunaan: {!! json_encode(__('app.profile.duty_status_required')) !!},
			unit_lama: {!! json_encode(__('app.profile.old_unit_required')) !!},
			kem_lama: {!! json_encode(__('app.profile.posting_authority_required')) !!},
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
