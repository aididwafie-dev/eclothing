@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit Base</div>
					<hr>
						<div class="containerMain">
							<div class="content">
									<B>Edit base details - {{ $base->base }}</B>
								<hr>
								<form autocomplete="off" method="post" action="{{ url('/save-edited-base') }}" name="save-base" id="save-base">

									<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
									<input type = "hidden" name="id" value = "{{ $base->id }}">

									<label class="label_">Base name</label>
											<input class="form-control" type = "text" name="base" maxlength="255" value="{{ old('base', $base->base) }}" required/>
									<br />
									<label class="label_">Org name</label>
											<input class="form-control" type="text" name="org_name" maxlength="255" value="{{ old('org_name', $base->org_name ?? '') }}" />
									<br />
									<label class="label_">Status</label>
										<select class="form-control" name="status">
											<option value="1" <?= (int) $base->status === 1 ? 'selected' : ''; ?>>Active</option>
											<option value="0" <?= (int) $base->status === 0 ? 'selected' : ''; ?>>Inactive</option>
											</select>
									<br />

									<label class="label_">Units under this base</label>
									<div class="shop-subtitle">Tick every unit that belongs to this base. Unticking one detaches it (it stays a unit, just without a base).</div>
									<div class="table-responsive" style="max-height: 320px; overflow-y: auto; border: 1px solid #e5e5e5; padding: 8px;">
										@foreach($units as $unit)
										<div class="checkbox">
											<label>
												<input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}" {{ (int) ($unit->base_hq ?? 0) === (int) $base->id ? 'checked' : '' }} />
												{{ $unit->value }}
												@if((int) ($unit->base_hq ?? 0) !== 0 && (int) ($unit->base_hq ?? 0) !== (int) $base->id)
													<span class="text-muted">(currently under another base)</span>
												@endif
											</label>
										</div>
										@endforeach
									</div>

									<div class="subBtn text-center"><br />
										<button class="btn btn-primary" type="submit" id="submit" name="submit">SAVE CHANGES</button>
										<a href="{{ url('/admin/base') }}" class="btn btn-default"> CANCEL</a>
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
