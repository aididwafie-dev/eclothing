@include('static-layout/header')
@include('static-layout/sidebar')

<br>
<div class="title"><i class="fa fa-shopping-bag" aria-hidden="true"></i> {{ __('app.orders.title') }}</div>
<hr>

<div class="containerMain">
	<div class="content table_center">
		@if($data != 0)
		<div class="orders-toolbar">
			<a class="mail_user_order_details">
				<button type="button" class="btn btn-brand"><i class="fa fa-envelope" aria-hidden="true"></i> {{ __('app.orders.send_mail') }}</button>
			</a>
		</div>
		<hr>
		<div class="shop-subtitle">{{ __('app.orders.intro') }}</div>

		<div class="table-responsive">
			<table class="table table-orders table-orders-member">
				<thead>
					<tr>
						<th>{{ __('app.orders.order') }}</th>
						<th>{{ __('app.orders.uniform') }}</th>
						<th>{{ __('app.orders.items') }}</th>
						<th>{{ __('app.orders.status') }}</th>
						<th>{{ __('app.orders.collection_date') }}</th>
						<th>{{ __('app.orders.last_updated') }}</th>
						<th><span class="sr-only">{{ __('app.orders.actions') }}</span></th>
					</tr>
				</thead>
				<tbody>
					@foreach($data as $array)
					@php
						$order = $array['userOrders'];
						$uniform = $array['orderedUniform'];
						$statusClass = !empty($order->status_class) ? $order->status_class : 'status-pending';
						// Translated from the status key, so the badge follows the
						// chosen language rather than the stored English label.
						$statusKey = !empty($order->status_key) ? $order->status_key : 'pending';
						$statusLabel = __('app.status.' . $statusKey);
						$remarks = trim((string) $order->remarks);
						$collectionDate = $order->collection_date ? date('d M Y', strtotime($order->collection_date)) : null;
						$detailsId = 'order-detail-' . $order->id;
						$uniformLabel = $uniform
							? $uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : '')
							: '-';
					@endphp
					<tr class="order-row" data-detail="{{ $detailsId }}">
						<td data-label="Order">
							<button type="button" class="order-row-toggle" aria-expanded="false" aria-controls="{{ $detailsId }}">
								<i class="fa fa-chevron-right order-row-caret" aria-hidden="true"></i>
								<span>#{{ $order->id }}</span>
								<span class="sr-only">{{ __('app.orders.show_details') }}</span>
							</button>
						</td>
						<td data-label="Uniform">{{ $uniformLabel }}</td>
						<td data-label="Items">{{ $array['orderCount'] }}</td>
						<td data-label="Status">
							<span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
							@if($remarks !== '')
							<i class="fa fa-comment-o order-row-remarks" aria-hidden="true" title="{{ __('app.orders.has_remarks') }}"></i>
							@endif
						</td>
						<td data-label="Collection Date">
							@if($collectionDate){{ $collectionDate }}@else<span class="text-muted">{{ __('app.orders.to_be_updated') }}</span>@endif
						</td>
						<td data-label="Last Updated">{{ $order->updated_at ? date('d M Y', strtotime($order->updated_at)) : '-' }}</td>
						<td data-label="Actions" class="order-row-action">
							<div class="order-row-buttons">
								{{-- Only a Pending order can still be changed; once the store
								     picks it up, checkout refuses to overwrite it. --}}
								@if(!empty($array['editable']))
								<form method="post" action="{{ route('user.order.edit', $order->id) }}" class="order-row-edit">
									@csrf
									<button type="submit" class="btn btn-brand btn-sm">
										<i class="fa fa-pencil" aria-hidden="true"></i> {{ __('app.orders.edit') }}
									</button>
								</form>
								@endif
								<a href="{{ route('user.order.kew-ps8', $order->id) }}" target="_blank" class="btn btn-default btn-sm">
									<i class="fa fa-file-text-o" aria-hidden="true"></i> KEW.PS-8
								</a>
							</div>
						</td>
					</tr>
					<tr id="{{ $detailsId }}" class="order-detail-row">
						<td colspan="7">
							<div class="order-detail-inner">
								<dl class="order-detail-meta">
									<div>
										<dt>{{ __('app.orders.ordered') }}</dt>
										<dd>{{ $order->created_at ? date('d M Y h:i A', strtotime($order->created_at)) : '-' }}</dd>
									</div>
									<div>
										<dt>{{ __('app.orders.last_updated') }}</dt>
										<dd>{{ $order->updated_at ? date('d M Y h:i A', strtotime($order->updated_at)) : '-' }}</dd>
									</div>
									<div>
										<dt>{{ __('app.orders.collection_date') }}</dt>
										<dd>{{ $collectionDate ?: __('app.orders.to_be_updated') }}</dd>
									</div>
									<div class="is-wide">
										<dt>{{ __('app.orders.remarks') }}</dt>
										<dd>{{ $remarks !== '' ? $remarks : __('app.orders.no_remarks') }}</dd>
									</div>
								</dl>

								<div class="order-detail-items-title">{{ __('app.orders.items_ordered') }}</div>
								<ul class="order-detail-items">
									@forelse($array['orderDetails'] as $clothsDetails)
									<li>
										<span class="order-detail-item-name">{{ $clothsDetails->clothes }}</span>
										<span class="order-detail-item-size">{{ __('app.orders.size') }} {{ $clothsDetails->size }}</span>
										<span class="order-detail-item-qty">&times; {{ $clothsDetails->quantity ?? 1 }}</span>
									</li>
									@empty
									<li class="is-empty">{{ __('app.orders.no_items') }}</li>
									@endforelse
								</ul>
							</div>
						</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		@else
		{{ __('app.orders.none_yet') }}
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

		// The whole row opens its detail row. The Order cell's button carries
		// the keyboard focus and aria-expanded; its click bubbles up to here,
		// so there is one handler. The Edit and KEW.PS-8 buttons do their own
		// thing and leave the row as it is.
		$(document).on('click', '.order-row', function(e) {
			if ($(e.target).closest('a, form').length) {
				return;
			}

			var $row = $(this);
			var isOpen = !$row.hasClass('is-open');

			$row.toggleClass('is-open', isOpen);
			$('#' + $row.data('detail')).toggleClass('is-open', isOpen);
			$row.find('.order-row-toggle').attr('aria-expanded', isOpen ? 'true' : 'false');
		});
	});

</script>
</body>

</html>
