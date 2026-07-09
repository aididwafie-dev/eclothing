@include('static-layout/header')
@include('static-layout/admin_sidebar')

@php
	$statusClass = !empty($order->status_class) ? $order->status_class : 'status-pending';
	$statusLabel = !empty($order->status_label) ? $order->status_label : 'Pending';
	if ($order->uniform_photo) {
		$image = glob("uploads/" . $order->uniform_photo);
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
									<td>{{ $cloth->clothes }}</td>
									<td>{{ $cloth->size }}</td>
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

						<div class="form-group">
							<label class="label_">Remarks</label>
							<textarea name="remarks" class="form-control" rows="5" placeholder="Add approval or rejection remarks here">{{ old('remarks', $order->remarks) }}</textarea>
						</div>

						<div class="form-group">
							<label class="label_">Collection Date</label>
							<input type="date" name="collection_date" class="form-control" value="{{ old('collection_date', $order->collection_date ? date('Y-m-d', strtotime($order->collection_date)) : '') }}">
							<p class="help-block">Leave empty if the collection date will be updated later.</p>
						</div>

						<div class="order-admin-actions">
							<button type="submit" name="status" value="3" class="btn btn-success btn-block"><i class="fa fa-check" aria-hidden="true"></i> Approve Order</button>
							<button type="submit" name="status" value="2" class="btn btn-danger btn-block"><i class="fa fa-times" aria-hidden="true"></i> Reject Order</button>
							<button type="submit" name="status" value="1" class="btn btn-warning btn-block"><i class="fa fa-clock-o" aria-hidden="true"></i> Mark Pending</button>
							<button type="submit" name="status" value="4" class="btn btn-default btn-block"><i class="fa fa-ban" aria-hidden="true"></i> Mark Expired</button>
						</div>
					</form>
				</div>
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
