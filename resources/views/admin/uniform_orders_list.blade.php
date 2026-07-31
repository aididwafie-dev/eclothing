@include('static-layout/header')
@include('static-layout/admin_sidebar')

<br>
<div class="title"><i class="fa fa-clipboard" aria-hidden="true"></i> Uniform Orders</div>
<hr>

<div class="containerMain">
	<div class="content full">
		<div class="shop-subtitle">Review all submitted uniform orders and open a detail page to approve or reject them.</div>
		<br>

		@php $search = isset($search) ? $search : ''; @endphp
		<form method="get" action="{{ route('admin.uniform-orders') }}" class="orders-search" role="search">
			<div class="orders-search-field">
				<i class="fa fa-search" aria-hidden="true"></i>
				<input type="text" name="search" value="{{ $search }}" class="form-control"
					placeholder="Search by Order ID (e.g. 1042)" inputmode="numeric"
					aria-label="Search by Order ID" autocomplete="off" />
			</div>
			<button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> Search</button>
			@if($search !== '')
			<a href="{{ route('admin.uniform-orders') }}" class="btn btn-default"><i class="fa fa-times" aria-hidden="true"></i> Clear</a>
			@endif
		</form>

		@if($search !== '' && $orders && $orders->count())
		<div class="orders-search-result">Showing results for Order ID <strong>#{{ $search }}</strong>.</div>
		@endif

		@if($orders && $orders->count())
		<div class="table-responsive">
			<table class="table table-orders table-orders-wide">
				<thead>
					<tr>
						<th>Order</th>
						<th>Service ID</th>
						<th>Name</th>
						<th>Unit</th>
						<th>Uniform</th>
						<th>Items</th>
						<th>Status</th>
						<th>Collection Date</th>
						<th>Ordered At</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					@foreach($orders as $order)
					@php
						$orderId = base64_encode('DCS'.$order->id.'DCS');
						$statusClass = !empty($order->status_class) ? $order->status_class : 'status-pending';
						$statusLabel = !empty($order->status_label) ? $order->status_label : 'Pending';
					@endphp
					<tr>
						<td data-label="Order">#{{ $order->id }}</td>
						<td data-label="Service ID">{{ $order->s_id ? $order->s_id : '-' }}</td>
						<td data-label="Name">{{ $order->name ? $order->name : 'N/A' }}</td>
						<td data-label="Unit">{{ $order->unit_name ? $order->unit_name : 'N/A' }}</td>
						<td data-label="Uniform">{{ $order->uniform_type }}{{ $order->uniform_name ? ' (' . $order->uniform_name . ')' : '' }}</td>
						<td data-label="Items">{{ $order->items_count }}</td>
						<td data-label="Status"><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
						<td data-label="Collection Date">{{ $order->collection_date ? date('d M Y', strtotime($order->collection_date)) : 'To be updated' }}</td>
						<td data-label="Ordered At">{{ $order->created_at ? date('d M Y h:i A', strtotime($order->created_at)) : '-' }}</td>
						<td data-label="Action"><a href="{{ route('admin.uniform-orders.show', ['id' => $orderId]) }}" class="btn btn-sm btn-default"><i class="fa fa-eye" aria-hidden="true"></i> View Detail</a></td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		@if($orders->hasPages())
		<div class="order-pagination">
			<a href="{{ $orders->previousPageUrl() ?: 'javascript:void(0)' }}" class="btn btn-sm btn-default{{ $orders->onFirstPage() ? ' disabled' : '' }}">Previous</a>
			<span class="order-pagination-label">Page {{ $orders->currentPage() }}</span>
			<a href="{{ $orders->nextPageUrl() ?: 'javascript:void(0)' }}" class="btn btn-sm btn-default{{ $orders->hasMorePages() ? '' : ' disabled' }}">Next</a>
		</div>
		@endif
		@else
		<div class="alert alert-info">
			@if($search !== '')
			No order found for Order ID <strong>#{{ $search }}</strong>.
			@else
			No uniform orders found.
			@endif
		</div>
		@endif
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
</body>
</html>
