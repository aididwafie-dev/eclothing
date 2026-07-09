@include('static-layout/header')
@include('static-layout/admin_sidebar')

<br>
<div class="title"><i class="fa fa-black-tie" aria-hidden="true"></i> Uniform Orders</div>
<hr>
@if($data != 0)
	@foreach($data as $orders)
		@php
			$stringId = "DCS".$orders['user_order']->id."DCS";
			$orderId = base64_encode($stringId);
			$stringId = "DCS".$orders['user_order']->user_id."DCS";
			$userId = base64_encode($stringId);
			$statusClass = !empty($orders['user_order']->status_class) ? $orders['user_order']->status_class : 'status-pending';
			$statusLabel = !empty($orders['user_order']->status_label) ? $orders['user_order']->status_label : 'Pending';
			$collectionDate = $orders['user_order']->collection_date ? date('d M Y', strtotime($orders['user_order']->collection_date)) : 'To be updated';
			$remarks = trim((string) $orders['user_order']->remarks);
		@endphp
		<div class="containerMain">
			<div class="content">
				<div class="order-card-header">
					<div>
						<div class="report-card-title">Uniform: {{ $orders['uniform_type']->uniform_type }}{{ $orders['uniform_type']->uniform_name ? ' (' . $orders['uniform_type']->uniform_name . ')' : '' }}</div>
						<div class="order-card-meta">Order #{{ $orders['user_order']->id }}</div>
					</div>
					<span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
				</div>
				<div class="orders-toolbar">
					<div>
						<a href="{{ url('edit/uniform_details/'.$orderId) }}"><i class="fa fa-edit" aria-hidden="true"></i> Edit</a>
						|
						<a href="{{ route('admin.uniform-orders.show', ['id' => $orderId]) }}"><i class="fa fa-eye" aria-hidden="true"></i> View Detail</a>
					</div>
					<a class="btn btn-xs btn-danger delete_order" data-url="{{url('delete-order/' . $userId . '/' . $orderId)}}"><span class="glyphicon glyphicon-trash"></span> Delete Order</a>
				</div>
				<hr>
				<div class="order-meta-block">
					<div class="order-info-row">
						<span class="order-info-label">Collection Date</span>
						<span class="order-info-value">{{ $collectionDate }}</span>
					</div>
					<div class="order-info-row">
						<span class="order-info-label">Remarks</span>
						<span class="order-info-value">{{ $remarks !== '' ? $remarks : 'No remarks yet.' }}</span>
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
							@foreach($orders['ordered_clothes'] as $ordered_clothes)
							<tr>
								<td>{{ $ordered_clothes->clothes }}</td>
								<td>{{ $ordered_clothes->size }}</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	@endforeach
@else
	<div class="container containerMain">
		<div class="content">
			This user has not placed any uniform order yet.
		</div>
	</div>
@endif
<!--#### 3 div open in sidebar ####-->
</div>	
</div>
</div>
<!--#### 3 div open in sidebar ####-->
</body>
</html>
<script>
$(document).ready(function() {
	$(".delete_order").click(function(){
		var delete_url = $(this).attr('data-url');
		var check = confirm('Are you sure you want to remove this order from the site permanently?');
		if(check == true)
		window.location.href = delete_url;
	});
})
</script>
