@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-cog" aria-hidden="true"></i> Tetapan Sistem</div>
<hr>
<div class="containerMain">
	<div class="content">
		@php
			$activeTab = Request::get('tab') === 'uniform' ? 'uniform' : 'system';
		@endphp

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation" class="{{ $activeTab === 'system' ? 'active' : '' }}"><a href="#tab-system-settings" aria-controls="tab-system-settings" role="tab" data-toggle="tab">System Setting</a></li>
			<li role="presentation" class="{{ $activeTab === 'uniform' ? 'active' : '' }}"><a href="#tab-uniform-settings" aria-controls="tab-uniform-settings" role="tab" data-toggle="tab">Uniform Setting</a></li>
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

				<form autocomplete="off" method="post" action="{{ url('/admin/system-settings?tab=uniform') }}" enctype="multipart/form-data">
					<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
					<input type="hidden" name="site_title" value="{{ old('site_title', $site_title ?? '') }}">

					@if(isset($uniforms) && $uniforms && count($uniforms))
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
								@foreach($uniforms as $uniform)
								@php
									$uniformImage = $uniform->uniform_photo ? url('uploads/' . $uniform->uniform_photo) : url('front_end/images/uniforms/' . $uniform->uniform_type . '.jpg');
								@endphp
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
								@endforeach
							</tbody>
						</table>
					</div>
					@else
					<div class="alert alert-info">Tiada uniform dijumpai.</div>
					@endif

					<div class="subBtn">
						<input class="btn btn-default text-uppercase" type="submit" value="Simpan" />
						<a class="btn btn-default" href="{{ url('/all-admins') }}">Kembali</a>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

</div>
</div>
</div>
</body>
</html>
