@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-cog" aria-hidden="true"></i> Tetapan Sistem</div>
<hr>
<div class="containerMain">
	<div class="content">
		@php
			$requestedTab = Request::get('tab');
			$activeTab = in_array($requestedTab, ['uniform', 'scale'], true) ? $requestedTab : 'system';
		@endphp

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation" class="{{ $activeTab === 'system' ? 'active' : '' }}"><a href="#tab-system-settings" aria-controls="tab-system-settings" role="tab" data-toggle="tab">System Setting</a></li>
			<li role="presentation" class="{{ $activeTab === 'uniform' ? 'active' : '' }}"><a href="#tab-uniform-settings" aria-controls="tab-uniform-settings" role="tab" data-toggle="tab">Uniform Setting</a></li>
			<li role="presentation" class="{{ $activeTab === 'scale' ? 'active' : '' }}"><a href="#tab-scale-settings" aria-controls="tab-scale-settings" role="tab" data-toggle="tab">Tetapan Skala Kelayakan Pakaian</a></li>
		</ul>

		<div class="tab-content" style="padding-top: 16px;">
			<div role="tabpanel" class="tab-pane {{ $activeTab === 'system' ? 'active' : '' }}" id="tab-system-settings">
				<div class="shop-subtitle">System settings (title and logo).</div>
				<br>

				<form autocomplete="off" method="post" action="{{ url('/admin/system-settings?tab=system') }}" enctype="multipart/form-data">
					<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">

					<div class="form-group">
						<label class="label_">Tajuk Sistem</label>
						<input class="form-control" type="text" name="site_title" value="{{ old('site_title', $site_title ?? '') }}" required />
					</div>

					<div class="form-group">
						<label class="label_">Logo Sistem (PNG/JPG)</label>
						<input class="form-control" type="file" name="logo" accept="image/png,image/jpeg" />
					</div>

					<div class="form-group">
						<label class="label_">Logo Semasa</label>
						<img src="{{ $siteLogoUrl ?? asset('front_end/images/logo.png') }}" class="img-responsive" alt="logo" style="max-height: 140px;" />
					</div>

					<div class="subBtn">
						<input class="btn btn-default text-uppercase" type="submit" value="Simpan" />
						<a class="btn btn-default" href="{{ url('/all-admins') }}">Kembali</a>
					</div>
				</form>
			</div>

			<div role="tabpanel" class="tab-pane {{ $activeTab === 'uniform' ? 'active' : '' }}" id="tab-uniform-settings">
				<div class="shop-subtitle">Kemaskini gambar dan maklumat bagi setiap uniform untuk paparan dalam shopping cart.</div>
				<br>

				@php
					$requestedUniformId = (int) Request::get('uniform_id');
					$uniformIdList = [];
					if (isset($uniforms) && $uniforms) {
						foreach ($uniforms as $uniformOption) {
							$uniformIdList[] = (int) $uniformOption->id;
						}
					}
					$selectedUniformId = in_array($requestedUniformId, $uniformIdList, true) ? $requestedUniformId : 0;
					if (!$selectedUniformId && count($uniformIdList)) {
						$selectedUniformId = $uniformIdList[0];
					}
				@endphp

				<div class="uniform-add-panel">
					<div class="uniform-add-title"><i class="fa fa-plus-circle" aria-hidden="true"></i> Tambah Kategori Uniform Baharu</div>
					<div class="uniform-add-hint">Kategori baharu akan terus muncul dalam tab <strong>Tetapan Skala Kelayakan Pakaian</strong> selepas disimpan.</div>

					<form autocomplete="off" method="post" action="{{ url('/admin/system-settings/uniform') }}" enctype="multipart/form-data">
						<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">

						<div class="row">
							<div class="col-sm-3">
								<div class="form-group">
									<label class="label_">Kod Jenis Uniform</label>
									<input class="form-control" type="text" name="uniform_type" maxlength="3" placeholder="cth: 3A" value="{{ old('uniform_type') }}" required />
									<p class="help-block">Maksimum 3 aksara.</p>
								</div>
							</div>
							<div class="col-sm-5">
								<div class="form-group">
									<label class="label_">Nama Uniform</label>
									<input class="form-control" type="text" name="uniform_name" placeholder="cth: BAJU KERJA" value="{{ old('uniform_name') }}" />
								</div>
							</div>
							<div class="col-sm-4">
								<div class="form-group">
									<label class="label_">Gambar Uniform (PNG/JPG)</label>
									<input class="form-control" type="file" name="uniform_photo" accept="image/png,image/jpeg" />
								</div>
							</div>
						</div>

						<div class="subBtn" style="text-align: left;">
							<input class="btn btn-primary text-uppercase" type="submit" value="Tambah Uniform" />
						</div>
					</form>
				</div>

				@if(isset($uniforms) && $uniforms && count($uniforms))
				<div class="form-group">
					<label class="label_">Pilih Jenis Uniform</label>
					<select class="form-control js-uniform-settings-selector">
						@foreach($uniforms as $uniform)
						@php
							$uniformLabel = $uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : '');
						@endphp
						<option value="{{ $uniform->id }}" {{ (int) $uniform->id === $selectedUniformId ? 'selected' : '' }}>{{ $uniformLabel }}</option>
						@endforeach
					</select>
				</div>
				@endif

				<form autocomplete="off" method="post" action="{{ url('/admin/system-settings?tab=uniform') }}" enctype="multipart/form-data">
					<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
					<input type="hidden" name="site_title" value="{{ old('site_title', $site_title ?? '') }}">
					<input type="hidden" name="selected_uniform_id" class="js-selected-uniform-id" value="{{ $selectedUniformId }}">

					@if(isset($uniforms) && $uniforms && count($uniforms))
					@foreach($uniforms as $uniform)
					@php
						$uniformImage = $uniform->uniform_photo
							? url(strpos($uniform->uniform_photo, '/') !== false ? $uniform->uniform_photo : 'uploads/' . $uniform->uniform_photo)
							: url('front_end/images/uniforms/' . $uniform->uniform_type . '.jpg');
						$uniformItems = isset($uniformItemsByUniform[$uniform->id]) ? $uniformItemsByUniform[$uniform->id] : [];
						$uniformLabel = $uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : '');
					@endphp
					<div class="uniform-settings-panel js-uniform-settings-panel{{ (int) $uniform->id === $selectedUniformId ? ' active' : '' }}" data-uniform-id="{{ $uniform->id }}">
						<div class="title" style="margin-top: 0;">{{ $uniformLabel }}</div>
						<div class="table-responsive">
							<table class="table table-orders">
								<thead>
									<tr>
										<th>Jenis Uniform</th>
										<th>Nama Uniform</th>
										<th>Gambar Semasa</th>
										<th>Muat Naik Gambar Baru</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td>
											<input class="form-control" type="text" name="uniforms[{{ $uniform->id }}][uniform_type]" value="{{ old('uniforms.' . $uniform->id . '.uniform_type', $uniform->uniform_type) }}" required />
										</td>
										<td>
											<input class="form-control" type="text" name="uniforms[{{ $uniform->id }}][uniform_name]" value="{{ old('uniforms.' . $uniform->id . '.uniform_name', $uniform->uniform_name) }}" />
										</td>
										<td>
											<img src="{{ $uniformImage }}" class="img-responsive" alt="uniform" style="max-height: 90px;" />
										</td>
										<td>
											<input class="form-control" type="file" name="uniform_photos[{{ $uniform->id }}]" accept="image/png,image/jpeg" />
										</td>
									</tr>
									<tr>
										<td colspan="4">
											<div class="shop-subtitle" style="margin-bottom: 12px;">Clothes & Accessories Images</div>
											@if(count($uniformItems))
											<div class="table-responsive">
												<table class="table table-bordered" style="margin-bottom: 0;">
													<thead>
														<tr>
															<th>Item Name</th>
															<th>Current Image</th>
															<th>Upload New Image</th>
														</tr>
													</thead>
													<tbody>
														@foreach($uniformItems as $uniformItem)
														@php
															$itemImage = !empty($uniformItem->clothes_photo)
																? url(strpos($uniformItem->clothes_photo, '/') !== false ? $uniformItem->clothes_photo : 'uploads/' . $uniformItem->clothes_photo)
																: '';
															$isAccessory = (int) $uniformItem->accessory === 1;
														@endphp
														<tr>
															<td>
																@if($isAccessory)
																<span title="Accessory" aria-label="Accessory" class="item-icon item-icon-accessory"><i class="fa fa-diamond" aria-hidden="true"></i></span>
																@else
																<span title="Clothes" aria-label="Clothes" class="item-icon item-icon-clothes"><i class="fa fa-shopping-bag" aria-hidden="true"></i></span>
																@endif
																{{ $uniformItem->clothes_type }}
															</td>
															<td>
																@if($itemImage)
																<img src="{{ $itemImage }}" class="img-responsive" alt="item" style="max-height: 72px;" />
																@else
																<span class="text-muted">No image uploaded</span>
																@endif
															</td>
															<td>
																<input class="form-control" type="file" name="uniform_clothes_photos[{{ $uniformItem->id }}]" accept="image/png,image/jpeg" />
															</td>
														</tr>
														@endforeach
													</tbody>
												</table>
											</div>
											@else
											<div class="text-muted">No clothes or accessories found for this uniform.</div>
											@endif
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					@endforeach
					@else
					<div class="alert alert-info">Tiada uniform dijumpai.</div>
					@endif

					<div class="subBtn">
						<input class="btn btn-default text-uppercase" type="submit" value="Simpan" />
						<a class="btn btn-default" href="{{ url('/all-admins') }}">Kembali</a>
					</div>
				</form>

				@if(isset($uniforms) && $uniforms && count($uniforms))
				<div class="uniform-add-panel">
					<div class="uniform-add-title"><i class="fa fa-plus-circle" aria-hidden="true"></i> Tambah Pakaian / Aksesori</div>
					<div class="uniform-add-hint">Item baharu ditambah kepada uniform yang dipilih di atas: <strong class="js-add-item-uniform-label"></strong></div>

					<form autocomplete="off" method="post" action="{{ url('/admin/system-settings/uniform-item') }}" enctype="multipart/form-data">
						<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
						<input type="hidden" name="uniforms_id" class="js-add-item-uniform-id" value="{{ $selectedUniformId }}">

						<div class="row">
							<div class="col-sm-4">
								<div class="form-group">
									<label class="label_">Nama Item</label>
									<input class="form-control" type="text" name="clothes_type" placeholder="cth: Inner Shirt" value="{{ old('clothes_type') }}" required />
								</div>
							</div>
							<div class="col-sm-2">
								<div class="form-group">
									<label class="label_">Jenis</label>
									<select class="form-control" name="item_kind">
										<option value="clothes" {{ old('item_kind') === 'accessory' ? '' : 'selected' }}>Pakaian</option>
										<option value="accessory" {{ old('item_kind') === 'accessory' ? 'selected' : '' }}>Aksesori</option>
									</select>
								</div>
							</div>
							<div class="col-sm-3">
								<div class="form-group">
									<label class="label_">Saiz</label>
									<input class="form-control" type="text" name="clothes_size" placeholder="cth: FIX atau S | M | L" value="{{ old('clothes_size') }}" />
									<p class="help-block">Kosong = tiada saiz (checkbox sahaja). <code>FIX</code> = saiz tetap. Senarai pilihan dipisahkan dengan <code>|</code>, julat dengan <code>-</code>.</p>
								</div>
							</div>
							<div class="col-sm-3">
								<div class="form-group">
									<label class="label_">Gambar Item (PNG/JPG)</label>
									<input class="form-control" type="file" name="clothes_photo" accept="image/png,image/jpeg" />
								</div>
							</div>
						</div>

						<div class="subBtn" style="text-align: left;">
							<input class="btn btn-primary text-uppercase" type="submit" value="Tambah Item" />
						</div>
					</form>
				</div>
				@endif

				<script>
					(function () {
						var selector = document.querySelector('.js-uniform-settings-selector');
						if (!selector) {
							return;
						}

						var hiddenInput = document.querySelector('.js-selected-uniform-id');
						var panels = document.querySelectorAll('.js-uniform-settings-panel');
						// The "add item" form sits outside the save form (forms cannot
						// nest), so it tracks the selected uniform through these.
						var addItemUniformId = document.querySelector('.js-add-item-uniform-id');
						var addItemUniformLabel = document.querySelector('.js-add-item-uniform-label');

						var updatePanels = function (uniformId) {
							if (hiddenInput) {
								hiddenInput.value = uniformId;
							}

							if (addItemUniformId) {
								addItemUniformId.value = uniformId;
							}

							if (addItemUniformLabel) {
								var option = selector.options[selector.selectedIndex];
								addItemUniformLabel.textContent = option ? option.text : '';
							}

							var url = new URL(window.location.href);
							url.searchParams.set('tab', 'uniform');
							url.searchParams.set('uniform_id', uniformId);
							window.history.replaceState({}, '', url.toString());

							Array.prototype.forEach.call(panels, function (panel) {
								if (panel.getAttribute('data-uniform-id') === uniformId) {
									panel.classList.add('active');
								} else {
									panel.classList.remove('active');
								}
							});
						};

						selector.addEventListener('change', function () {
							updatePanels(this.value);
						});

						updatePanels(selector.value);
					})();
				</script>
			</div>

			<div role="tabpanel" class="tab-pane {{ $activeTab === 'scale' ? 'active' : '' }}" id="tab-scale-settings">
				<div class="shop-subtitle">Tetapkan bilangan helai/pasang yang dibenarkan bagi setiap pangkat <strong>AIR FORCE</strong>. Pilih pangkat, isi bilangan untuk setiap pakaian dan aksesori, kemudian simpan.</div>
				<br>

				@php
					$officerRanks = isset($ranksByCategory['officer']) ? $ranksByCategory['officer'] : [];
					$nonOfficerRanks = isset($ranksByCategory['non_officer']) ? $ranksByCategory['non_officer'] : [];
					$rankScales = isset($rankScales) && is_array($rankScales) ? $rankScales : [];
					$hasRanks = count($officerRanks) || count($nonOfficerRanks);
				@endphp

				@if(!($scaleStorageReady ?? false))
				<div class="alert alert-warning">
					Jadual <code>uniform_scales</code> belum wujud. Jalankan <code>php artisan migrate</code> untuk mengaktifkan tetapan ini.
				</div>
				@endif

				@if(!$hasRanks)
				<div class="alert alert-info">Tiada pangkat AIR FORCE dijumpai.</div>
				@else

				<div class="scale-legend">
					<span class="scale-legend-item"><span class="scale-chip scale-chip-unset">Kosong</span> Tiada had &mdash; ahli boleh memilih item ini seperti biasa.</span>
					<span class="scale-legend-item"><span class="scale-chip scale-chip-blocked">0</span> Tidak layak &mdash; item disembunyikan semasa membuat pesanan.</span>
					<span class="scale-legend-item"><span class="scale-chip scale-chip-set">1+</span> Had maksimum bilangan helai/pasang.</span>
				</div>

				<div class="scale-autosave js-scale-form" data-pangkat-id="{{ $selectedRankId ?? 0 }}">
					<input type="hidden" class="js-scale-rank-id" value="{{ $selectedRankId ?? 0 }}">

					<div class="form-group">
						<label class="label_" for="scaleRankSelector">Pilih Pangkat (AIR FORCE)</label>
						<select class="form-control js-scale-rank-selector" id="scaleRankSelector">
							@if(count($officerRanks))
							<optgroup label="Pegawai (Officer)">
								@foreach($officerRanks as $rank)
								<option value="{{ $rank->id }}" {{ (int) $rank->id === (int) ($selectedRankId ?? 0) ? 'selected' : '' }}>{{ $rank->value }}</option>
								@endforeach
							</optgroup>
							@endif
							@if(count($nonOfficerRanks))
							<optgroup label="Bukan Pegawai (Non-Officer)">
								@foreach($nonOfficerRanks as $rank)
								<option value="{{ $rank->id }}" {{ (int) $rank->id === (int) ($selectedRankId ?? 0) ? 'selected' : '' }}>{{ $rank->value }}</option>
								@endforeach
							</optgroup>
							@endif
						</select>
						<p class="help-block">Menukar pangkat akan memuat semula skala bagi pangkat tersebut.</p>
					</div>

					@php
						$hiddenUniformIds = isset($hiddenUniformIds) && is_array($hiddenUniformIds) ? $hiddenUniformIds : [];
					@endphp

					@if(!($hiddenStorageReady ?? false))
					<div class="alert alert-warning">
						Jadual <code>uniform_rank_hidden</code> belum wujud. Jalankan <code>php artisan migrate</code> untuk mengaktifkan sembunyi uniform.
					</div>
					@endif

					@if(isset($uniforms) && $uniforms && count($uniforms))
					<div class="scale-visibility">
						<div class="scale-visibility-head">
							<div class="scale-visibility-title"><i class="fa fa-eye-slash" aria-hidden="true"></i> Sembunyikan Uniform dari Troli Pesanan</div>
							<div class="scale-visibility-hint">Tanda = uniform tidak dipaparkan semasa ahli pangkat ini membuat pesanan. Perubahan disimpan automatik.</div>
						</div>
						<div class="scale-visibility-grid">
							@foreach($uniforms as $uniform)
							@php
								$isHidden = in_array((int) $uniform->id, $hiddenUniformIds, true);
								$visLabel = $uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : '');
							@endphp
							<label class="scale-visibility-item{{ $isHidden ? ' is-hidden' : '' }}">
								<input type="checkbox" class="js-uniform-hide" data-uniform-id="{{ $uniform->id }}" {{ $isHidden ? 'checked' : '' }} />
								<span class="scale-visibility-name">{{ $visLabel }}</span>
							</label>
							@endforeach
						</div>
					</div>
					@endif

					@if(isset($uniforms) && $uniforms && count($uniforms))
						@foreach($uniforms as $uniform)
						@php
							$scaleItems = isset($uniformItemsByUniform[$uniform->id]) ? $uniformItemsByUniform[$uniform->id] : [];
							$scaleUniformLabel = $uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : '');
						@endphp
						@php $groupHidden = in_array((int) $uniform->id, $hiddenUniformIds, true); @endphp
						<div class="scale-group{{ $groupHidden ? ' is-uniform-hidden' : '' }}" data-uniform-id="{{ $uniform->id }}">
							<div class="scale-group-title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> {{ $scaleUniformLabel }} <span class="scale-group-hidden-tag">Disembunyikan dari troli</span></div>
							<div class="table-responsive">
								<table class="table table-orders">
									<thead>
										<tr>
											<th>Item</th>
											<th>Jenis</th>
											<th style="width: 190px;">Bilangan Dibenarkan</th>
										</tr>
									</thead>
									<tbody>
										@foreach($scaleItems as $scaleItem)
										@php
											$itemId = (int) $scaleItem->id;
											$isAccessoryItem = (int) $scaleItem->accessory === 1;
											$currentScale = array_key_exists($itemId, $rankScales) ? $rankScales[$itemId] : null;
											$scaleValue = old('scales.' . $itemId, $currentScale === null ? '' : $currentScale);
										@endphp
										<tr>
											<td data-label="Item">
												@if($isAccessoryItem)
												<span title="Aksesori" aria-label="Aksesori" class="item-icon item-icon-accessory"><i class="fa fa-diamond" aria-hidden="true"></i></span>
												@else
												<span title="Pakaian" aria-label="Pakaian" class="item-icon item-icon-clothes"><i class="fa fa-shopping-bag" aria-hidden="true"></i></span>
												@endif
												{{ $scaleItem->clothes_type }}
											</td>
											<td data-label="Jenis">{{ $isAccessoryItem ? 'Aksesori' : 'Pakaian' }}</td>
											<td data-label="Bilangan Dibenarkan">
												<input class="form-control scale-input js-scale-input" type="number" min="0" max="999" step="1"
													data-item-id="{{ $itemId }}"
													value="{{ $scaleValue }}"
													placeholder="Tiada had"
													aria-label="Bilangan dibenarkan untuk {{ $scaleItem->clothes_type }}" />
											</td>
										</tr>
										@endforeach
										@if(!count($scaleItems))
										<tr>
											<td colspan="3" class="text-muted">Tiada pakaian atau aksesori lagi. Tambah item di tab <strong>Uniform Setting</strong>, kemudian tetapkan skala di sini.</td>
										</tr>
										@endif
									</tbody>
								</table>
							</div>
						</div>
						@endforeach
					@else
					<div class="alert alert-info">Tiada uniform dijumpai.</div>
					@endif

					<div class="scale-autosave-note">
						<i class="fa fa-check-circle" aria-hidden="true"></i> Semua perubahan disimpan secara automatik &mdash; tiada butang Simpan diperlukan.
					</div>
				</div>

				<div class="scale-toast" id="scaleToast" role="status" aria-live="polite"></div>

				<script>
					(function () {
						var form = document.querySelector('.js-scale-form');
						var selector = document.querySelector('.js-scale-rank-selector');
						if (!form || !selector) {
							return;
						}

						var pangkatId = form.getAttribute('data-pangkat-id');
						var token = (document.querySelector('meta[name="_token"]') || {}).content || '';
						var toast = document.getElementById('scaleToast');
						var toastTimer = null;

						function showToast(message, isError) {
							if (!toast) { return; }
							toast.textContent = message;
							toast.classList.remove('is-error', 'is-shown');
							if (isError) { toast.classList.add('is-error'); }
							// reflow so the transition replays on rapid successive saves
							void toast.offsetWidth;
							toast.classList.add('is-shown');
							if (toastTimer) { clearTimeout(toastTimer); }
							toastTimer = setTimeout(function () { toast.classList.remove('is-shown'); }, 1600);
						}

						function post(url, data, onOk) {
							var body = new URLSearchParams(data);
							body.append('_token', token);
							fetch(url, {
								method: 'POST',
								headers: {
									'X-CSRF-TOKEN': token,
									'X-Requested-With': 'XMLHttpRequest',
									'Content-Type': 'application/x-www-form-urlencoded'
								},
								body: body.toString()
							}).then(function (r) {
								return r.json().then(function (j) { return { ok: r.ok, body: j }; });
							}).then(function (res) {
								if (res.ok && res.body && res.body.ok) {
									showToast('Disimpan', false);
									if (onOk) { onOk(res.body); }
								} else {
									showToast((res.body && res.body.message) || 'Gagal menyimpan', true);
								}
							}).catch(function () {
								showToast('Gagal menyimpan', true);
							});
						}

						// Switching rank reloads so the loaded values match the rank -- a
						// half-edited client cache would post one rank's data as another's.
						selector.addEventListener('change', function () {
							var url = new URL(window.location.href);
							url.searchParams.set('tab', 'scale');
							url.searchParams.set('pangkat_id', this.value);
							window.location.href = url.toString();
						});

						// Enable/disable a uniform's quantity fields to mirror its
						// hidden state -- quantities are moot while the whole uniform
						// is hidden from the cart.
						function applyGroupHidden(uniformId, hidden) {
							var group = document.querySelector('.scale-group[data-uniform-id="' + uniformId + '"]');
							if (!group) { return; }
							group.classList.toggle('is-uniform-hidden', hidden);
							Array.prototype.forEach.call(group.querySelectorAll('.js-scale-input'), function (input) {
								input.disabled = hidden;
							});
						}

						// --- uniform visibility tickboxes (auto-save) ---
						Array.prototype.forEach.call(document.querySelectorAll('.js-uniform-hide'), function (box) {
							applyGroupHidden(box.getAttribute('data-uniform-id'), box.checked);
							box.addEventListener('change', function () {
								var uniformId = box.getAttribute('data-uniform-id');
								var hidden = box.checked;
								box.closest('.scale-visibility-item').classList.toggle('is-hidden', hidden);
								applyGroupHidden(uniformId, hidden);
								post('{{ url('/admin/scale/uniform-visibility') }}', {
									pangkat_id: pangkatId,
									uniforms_id: uniformId,
									hidden: hidden ? '1' : '0'
								}, null);
							});
						});

						// Tint each quantity field by state (blank / 0 / n).
						function paint(input) {
							var raw = input.value.trim();
							input.classList.remove('is-scale-unset', 'is-scale-blocked', 'is-scale-set');
							if (raw === '') {
								input.classList.add('is-scale-unset');
							} else if (parseInt(raw, 10) === 0) {
								input.classList.add('is-scale-blocked');
							} else {
								input.classList.add('is-scale-set');
							}
						}

						// --- quantity fields (auto-save on change) ---
						Array.prototype.forEach.call(document.querySelectorAll('.js-scale-input'), function (input) {
							paint(input);
							input.addEventListener('input', function () { paint(input); });
							input.addEventListener('change', function () {
								paint(input);
								post('{{ url('/admin/scale/item') }}', {
									pangkat_id: pangkatId,
									uniform_clothes_id: input.getAttribute('data-item-id'),
									value: input.value.trim()
								}, null);
							});
						});
					})();
				</script>
				@endif
			</div>
		</div>
	</div>
</div>

</div>
</div>
</div>
</body>
</html>
