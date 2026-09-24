@include('static-layout/header')
@include('static-layout/admin_sidebar')

@php
	$statusClass = !empty($order->status_class) ? $order->status_class : 'status-pending';
	$statusLabel = __('app.status.' . (!empty($order->status_key) ? $order->status_key : 'pending'));
	if ($order->uniform_photo) {
		$image = glob(strpos($order->uniform_photo, '/') !== false ? $order->uniform_photo : "uploads/" . $order->uniform_photo);
	} else {
		$image = glob("front_end/images/uniforms/" . $order->uniform_type . ".jpg");
	}
@endphp

<br>
<div class="title"><i class="fa fa-folder-open" aria-hidden="true"></i> {{ __('app.admin_detail.title') }}</div>
<hr>

<div class="containerMain">
	<div class="content">
		<a href="{{ route('admin.uniform-orders') }}" class="btn btn-default"><i class="fa fa-arrow-left" aria-hidden="true"></i> {{ __('app.admin_detail.back') }}</a>
		<hr>

		<div class="order-card">
			<div class="order-card-header">
				<div>
					<div class="report-card-title">{{ $order->uniform_type }}{{ $order->uniform_name ? ' (' . $order->uniform_name . ')' : '' }}</div>
					<div class="order-card-meta">{{ __('app.admin_detail.order_no', ['id' => $order->id]) }}</div>
				</div>
				<span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
			</div>

			<div class="order-detail-layout">
				<div class="order-detail-main">
					<div class="order-summary-grid">
						<div class="order-photo-block">
							@if($image)
							<img src="../../{{$image[0]}}" class="uniform_photo order-photo-preview" alt="Uniform photo" />
							@endif
						</div>
						<div class="order-meta-block">
							<div class="order-info-row">
								<span class="order-info-label">{{ __('app.admin_detail.service_id') }}</span>
								<span class="order-info-value">{{ $order->s_id ? $order->s_id : '-' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">{{ __('app.admin_detail.user') }}</span>
								<span class="order-info-value">{{ $order->name ? $order->name : 'N/A' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">{{ __('app.admin_detail.email') }}</span>
								<span class="order-info-value">{{ $order->email ? $order->email : 'N/A' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">{{ __('app.admin_detail.unit') }}</span>
								<span class="order-info-value">{{ $order->unit_name ? $order->unit_name : 'N/A' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">{{ __('app.admin_detail.ordered_at') }}</span>
								<span class="order-info-value">{{ $order->created_at ? date('d M Y h:i A', strtotime($order->created_at)) : '-' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">{{ __('app.admin_detail.collection_date') }}</span>
								<span class="order-info-value">{{ $order->collection_date ? date('d M Y', strtotime($order->collection_date)) : __('app.admin_detail.to_be_updated') }}</span>
							</div>
						</div>
					</div>

					<div class="table-responsive">
						<table class="table table-orders">
							<thead>
								<tr>
									<th>{{ __('app.admin_detail.clothing_item') }}</th>
									<th>{{ __('app.admin_detail.size_ordered') }}</th>
								</tr>
							</thead>
							<tbody>
								@foreach($ordered_clothes as $cloth)
								<tr>
									<td data-label="{{ __('app.admin_detail.clothing_item') }}">{{ $cloth->clothes }}</td>
									<td data-label="{{ __('app.admin_detail.size_ordered') }}">{{ $cloth->size }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				<div class="order-detail-sidebar">
					<form method="post" action="{{ route('admin.uniform-orders.update') }}">
						{{ csrf_field() }}
						<input type="hidden" name="order_id" value="{{ $order->id }}">

						<div class="order-actions-heading" style="padding-top:0;border-top:0;">{{ __('app.admin_detail.review') }}</div>

						@php $remarksInvalid = $errors->has('remarks'); @endphp
						<div class="form-group{{ $remarksInvalid ? ' has-error' : '' }}">
							<label class="label_" for="orderRemarks">{{ __('app.admin_detail.remarks') }}</label>
							<textarea id="orderRemarks" name="remarks" class="form-control{{ $remarksInvalid ? ' field-invalid' : '' }}" rows="4"
								placeholder="{{ __('app.admin_detail.remarks_placeholder') }}"
								aria-describedby="orderRemarksError">{{ old('remarks', $order->remarks) }}</textarea>
							{{-- Shown when a rejection is attempted with no reason: the
							     member is told why their order was rejected, so the
							     field cannot be left blank. --}}
							<p id="orderRemarksError" class="field-error-text" @if(!$remarksInvalid) style="display:none;" @endif>{{ $errors->first('remarks') ?: __('app.admin_detail.remarks_required') }}</p>
						</div>

						<div class="form-group">
							<label class="label_" for="orderCollectionDate">{{ __('app.admin_detail.collection_date') }}</label>
							<input id="orderCollectionDate" type="date" name="collection_date" class="form-control" value="{{ old('collection_date', $order->collection_date ? date('Y-m-d', strtotime($order->collection_date)) : '') }}">
							<p class="help-block">{{ __('app.admin_detail.collection_date_help') }}</p>
						</div>

						<div class="order-actions-heading">{{ __('app.admin_detail.set_status') }}</div>

						@php
							// Driven by the admin's role, so an account limited to
							// servicing the queue is not shown decisions it cannot make.
							$allowedStatuses = $allowedStatuses ?? ['1', '2', '3', '4', '5', '6'];
							$statusButtons = [
								['code' => '5', 'class' => 'btn-info',    'icon' => 'fa-cogs',           'label' => __('app.admin_detail.mark_processing')],
								['code' => '3', 'class' => 'btn-success', 'icon' => 'fa-check',          'label' => __('app.admin_detail.approve')],
								['code' => '6', 'class' => 'btn-primary', 'icon' => 'fa-flag-checkered', 'label' => __('app.admin_detail.mark_completed')],
								['code' => '2', 'class' => 'btn-danger',  'icon' => 'fa-times',          'label' => __('app.admin_detail.reject')],
								['code' => '1', 'class' => 'btn-warning', 'icon' => 'fa-clock-o',        'label' => __('app.admin_detail.mark_pending')],
								['code' => '4', 'class' => 'btn-default', 'icon' => 'fa-ban',            'label' => __('app.admin_detail.mark_expired')],
							];
						@endphp
						<div class="order-admin-actions">
							@foreach($statusButtons as $statusButton)
							@if(in_array($statusButton['code'], $allowedStatuses, true))
							<button type="submit" name="status" value="{{ $statusButton['code'] }}" class="btn {{ $statusButton['class'] }} btn-block"><i class="fa {{ $statusButton['icon'] }}" aria-hidden="true"></i> {{ $statusButton['label'] }}</button>
							@endif
							@endforeach
						</div>
					</form>
				</div>
			</div>
		</div>

		@php $kewPs8PreviewUrl = route('admin.uniform-orders.kew-ps8', ['id' => $orderKey, 'preview' => 1]); @endphp
		<div class="order-card">
			<div class="order-card-header">
				<div>
					<div class="report-card-title">KEW.PS-8</div>
					<div class="order-card-meta">{{ __('app.admin_detail.kew_subtitle', ['ref' => $orderReference]) }}</div>
				</div>
			</div>

			{{-- The preview is the same PDF the button below downloads, served
			     inline, so there is no second rendering path to keep in step.
			     Loaded lazily: an admin who only came to set a status should not
			     pay for a PDF render they never scroll to. --}}
			<iframe class="kewps8-preview-frame" src="{{ $kewPs8PreviewUrl }}"
				title="{{ __('app.admin_detail.preview_title') }}" loading="lazy"></iframe>
			<p class="help-block">{{ __('app.admin_detail.preview_missing') }} <a href="{{ $kewPs8PreviewUrl }}" target="_blank" rel="noopener">{{ __('app.admin_detail.open_new_tab') }}</a>.</p>

			<div class="kewps8-preview-actions">
				<a href="{{ route('admin.uniform-orders.kew-ps8', ['id' => $orderKey]) }}" class="btn btn-brand">
					<i class="fa fa-download" aria-hidden="true"></i> {{ __('app.admin_detail.download_kew') }}
				</a>
			</div>
		</div>
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
<script type="text/javascript">
	$(document).ready(function() {
		var REJECT_STATUS = '2';
		var REASON_REQUIRED = {!! json_encode(__('app.admin_detail.reason_required_popup')) !!};

		var $form = $('.order-detail-sidebar form');
		var $remarks = $('#orderRemarks');
		var $remarksError = $('#orderRemarksError');
		var clickedStatus = null;

		// Which button was pressed decides whether a reason is required, so it
		// is recorded before the form's own submit handler runs.
		$form.on('click', 'button[type="submit"]', function() {
			clickedStatus = $(this).val();
		});

		$remarks.on('input', function() {
			if ($.trim($remarks.val()) !== '') {
				$remarks.removeClass('field-invalid').closest('.form-group').removeClass('has-error');
				$remarksError.hide();
			}
		});

		$form.on('submit', function(e) {
			if (clickedStatus !== REJECT_STATUS || $.trim($remarks.val()) !== '') {
				return;
			}

			e.preventDefault();

			$remarks.addClass('field-invalid').closest('.form-group').addClass('has-error');
			$remarksError.show();

			if (window.showAppPopup) {
				// The popup takes focus while it is open, so the field is
				// focused once the admin dismisses it.
				$('#appPopupModal').one('hidden.bs.modal', function() {
					$remarks.focus();
				});
				window.showAppPopup(REASON_REQUIRED, 'danger', { autoClose: false });
			} else {
				$remarks.focus();
			}
		});
	});
</script>
</body>
</html>
