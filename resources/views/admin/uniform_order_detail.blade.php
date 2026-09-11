@include('static-layout/header')
@include('static-layout/admin_sidebar')

@php
	$statusClass = !empty($order->status_class) ? $order->status_class : 'status-pending';
	$statusLabel = !empty($order->status_label) ? $order->status_label : 'Pending';
	if ($order->uniform_photo) {
		$image = glob(strpos($order->uniform_photo, '/') !== false ? $order->uniform_photo : "uploads/" . $order->uniform_photo);
	} else {
		$image = glob("front_end/images/uniforms/" . $order->uniform_type . ".jpg");
	}
@endphp

<br>
<div class="title"><i class="fa fa-folder-open" aria-hidden="true"></i> Uniform Order Detail</div>
<hr>

<div class="containerMain">
	<div class="content">
		<a href="{{ route('admin.uniform-orders') }}" class="btn btn-default"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back to Orders</a>
		<hr>

		<div class="order-card">
			<div class="order-card-header">
				<div>
					<div class="report-card-title">{{ $order->uniform_type }}{{ $order->uniform_name ? ' (' . $order->uniform_name . ')' : '' }}</div>
					<div class="order-card-meta">Order #{{ $order->id }}</div>
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
								<span class="order-info-label">Service ID</span>
								<span class="order-info-value">{{ $order->s_id ? $order->s_id : '-' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">User</span>
								<span class="order-info-value">{{ $order->name ? $order->name : 'N/A' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">Email</span>
								<span class="order-info-value">{{ $order->email ? $order->email : 'N/A' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">Unit</span>
								<span class="order-info-value">{{ $order->unit_name ? $order->unit_name : 'N/A' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">Ordered At</span>
								<span class="order-info-value">{{ $order->created_at ? date('d M Y h:i A', strtotime($order->created_at)) : '-' }}</span>
							</div>
							<div class="order-info-row">
								<span class="order-info-label">Collection Date</span>
								<span class="order-info-value">{{ $order->collection_date ? date('d M Y', strtotime($order->collection_date)) : 'To be updated' }}</span>
							</div>
						</div>
					</div>

					<div class="table-responsive">
						<table class="table table-orders">
							<thead>
								<tr>
									<th>Clothing Item</th>
									<th>Size Ordered</th>
								</tr>
							</thead>
							<tbody>
								@foreach($ordered_clothes as $cloth)
								<tr>
									<td data-label="Clothing Item">{{ $cloth->clothes }}</td>
									<td data-label="Size Ordered">{{ $cloth->size }}</td>
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

						<div class="order-actions-heading" style="padding-top:0;border-top:0;">Review this order</div>

						<div class="form-group">
							<label class="label_" for="orderRemarks">Remarks</label>
							<textarea id="orderRemarks" name="remarks" class="form-control" rows="4" placeholder="Add approval or rejection remarks here">{{ old('remarks', $order->remarks) }}</textarea>
						</div>

						<div class="form-group">
							<label class="label_" for="orderCollectionDate">Collection Date</label>
							<input id="orderCollectionDate" type="date" name="collection_date" class="form-control" value="{{ old('collection_date', $order->collection_date ? date('Y-m-d', strtotime($order->collection_date)) : '') }}">
							<p class="help-block">Leave empty if the collection date will be updated later.</p>
						</div>

						<div class="order-actions-heading">Set status</div>

						@php
							// Driven by the admin's role, so an account limited to
							// servicing the queue is not shown decisions it cannot make.
							$allowedStatuses = $allowedStatuses ?? ['1', '2', '3', '4', '5', '6'];
							$statusButtons = [
								['code' => '5', 'class' => 'btn-info',    'icon' => 'fa-cogs',           'label' => 'Mark Processing'],
								['code' => '3', 'class' => 'btn-success', 'icon' => 'fa-check',          'label' => 'Approve Order'],
								['code' => '6', 'class' => 'btn-primary', 'icon' => 'fa-flag-checkered', 'label' => 'Mark Completed'],
								['code' => '2', 'class' => 'btn-danger',  'icon' => 'fa-times',          'label' => 'Reject Order'],
								['code' => '1', 'class' => 'btn-warning', 'icon' => 'fa-clock-o',        'label' => 'Mark Pending'],
								['code' => '4', 'class' => 'btn-default', 'icon' => 'fa-ban',            'label' => 'Mark Expired'],
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
					<div class="order-card-meta">Borang Permohonan Stok &mdash; {{ $orderReference }}</div>
				</div>
			</div>

			{{-- The preview is the same PDF the button below downloads, served
			     inline, so there is no second rendering path to keep in step.
			     Loaded lazily: an admin who only came to set a status should not
			     pay for a PDF render they never scroll to. --}}
			<iframe class="kewps8-preview-frame" src="{{ $kewPs8PreviewUrl }}"
				title="Pratonton borang KEW.PS-8" loading="lazy"></iframe>
			<p class="help-block">Preview not showing? <a href="{{ $kewPs8PreviewUrl }}" target="_blank" rel="noopener">Open the form in a new tab</a>.</p>

			<div class="kewps8-preview-actions">
				<a href="{{ route('admin.uniform-orders.kew-ps8', ['id' => $orderKey]) }}" class="btn btn-brand">
					<i class="fa fa-download" aria-hidden="true"></i> Download KEW.PS-8
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
</body>
</html>
