@include('static-layout/header')
@include('static-layout/sidebar')

<br>
<div class="title"><i class="fa fa-shopping-bag" aria-hidden="true"></i> Uniform Order Status</div>
<hr>

<div class="containerMain">
	<div class="content table_center">
		@if($data != 0)
		<div class="orders-toolbar">
			<a class="mail_user_order_details">
				<button type="button" class="btn btn-brand"><i class="fa fa-envelope" aria-hidden="true"></i> Send Mail</button>
			</a>
			<a class="delete_user_order">
				<button type="button" class="btn btn-danger"><i class="fa fa-trash" aria-hidden="true"></i> Delete Order</button>
			</a>
		</div>
		<hr>
		<div class="shop-subtitle">Track each uniform order by its current approval status.</div>

		@foreach($data as $array)
		@php
			$statusClass = !empty($array['userOrders']->status_class) ? $array['userOrders']->status_class : 'status-pending';
			$statusLabel = !empty($array['userOrders']->status_label) ? $array['userOrders']->status_label : 'Pending';
			$remarks = trim((string) $array['userOrders']->remarks);
			$collectionDate = $array['userOrders']->collection_date ? date('d M Y', strtotime($array['userOrders']->collection_date)) : 'To be updated';
			$detailsId = 'order-details-' . $array['userOrders']->id;
		@endphp
		<div class="order-card">
			<div class="order-card-header">
				<div>
					<div class="report-card-title">{{ $array['orderedUniform']->uniform_type }}{{ $array['orderedUniform']->uniform_name ? ' (' . $array['orderedUniform']->uniform_name . ')' : '' }}</div>
					<div class="order-card-meta">Items ordered: {{ $array['orderCount'] }}</div>
				</div>
				<span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
			</div>

			<div class="order-card-actions">
				<a href="{{ route('user.order.kew-ps8', $array['userOrders']->id) }}" target="_blank" class="btn btn-default btn-sm">
					<i class="fa fa-file-text-o" aria-hidden="true"></i> Jana Borang KEW.PS-8
				</a>
				<a href="javascript:void(0)" class="btn btn-brand btn-sm order-details-toggle" data-target="#{{ $detailsId }}" aria-expanded="false">
					<i class="fa fa-chevron-down" aria-hidden="true"></i> <span>Show Details</span>
				</a>
			</div>

			<div class="order-summary-table">
				<div class="order-summary-row">
					<div class="order-summary-cell">
						<span class="order-info-label">Collection Date</span>
						<span class="order-info-value">{{ $collectionDate }}</span>
					</div>
					<div class="order-summary-cell">
						<span class="order-info-label">Remarks</span>
						<span class="order-info-value">{{ $remarks !== '' ? $remarks : 'No remarks yet.' }}</span>
					</div>
					<div class="order-summary-cell">
						<span class="order-info-label">Last Updated</span>
						<span class="order-info-value">{{ $array['userOrders']->updated_at ? date('d M Y h:i A', strtotime($array['userOrders']->updated_at)) : '-' }}</span>
					</div>
					<div class="order-summary-cell">
						<span class="order-info-label">Items Ordered</span>
						<span class="order-info-value">{{ $array['orderCount'] }}</span>
					</div>
				</div>
			</div>

			<div id="{{ $detailsId }}" class="order-details-panel" style="display: none;">
				<div class="table-responsive">
					<table class="table table-orders">
						<thead>
							<tr>
								<th>Clothe Name</th>
								<th>Size Ordered</th>
							</tr>
						</thead>
						<tbody>
							@foreach($array['orderDetails'] as $clothsDetails)
							<tr>
								<td data-label="Clothing Item">{{ $clothsDetails->clothes }}</td>
								<td data-label="Size Ordered">{{ $clothsDetails->size }}</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
		@endforeach
		@else
		You have not ordered any uniform.
		@endif
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
<script type="text/javascript">
	$(document).ready(function() {
		$(".mail_user_order_details").click(function() {
			showAppPopup('Sending mail....', 'info', { title: 'Please Wait', autoClose: false });
			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
				}
			});
			$.ajax({
				type: 'post',
				url: '/ajax-mail-user-order-details',
				success: function(result) {
					showAppPopup(result, 'success');
				}
			});
		});

		$(".delete_user_order").click(function() {
			var check = confirm('Are you sure you would like to delete your order?');
			if (check == true) {
				showAppPopup('Deleting your order...', 'warning', { title: 'Please Wait', autoClose: false });
				$.ajaxSetup({
					headers: {
						'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
					}
				});
				$.ajax({
					type: 'post',
					url: '/ajax-delete-user-order',
					success: function(result) {
						showAppPopup(result, 'success');
						setTimeout(function() {
							window.location.reload();
						}, 2000);
					}
				});
			}
		});

		$(document).on('click', '.order-details-toggle', function() {
			var $btn = $(this);
			var target = $btn.data('target');
			var $panel = $(target);
			var isVisible = $panel.is(':visible');

			$panel.stop(true, true).slideToggle(180);
			$btn.attr('aria-expanded', isVisible ? 'false' : 'true');
			$btn.find('span').text(isVisible ? 'Show Details' : 'Hide Details');
			$btn.find('i.fa').toggleClass('fa-chevron-down', isVisible).toggleClass('fa-chevron-up', !isVisible);
		});
	});

</script>
</body>

</html>
