@include('static-layout/header')
@include('static-layout/sidebar')

<br>
<div class="title"><i class="fa fa-shopping-bag" aria-hidden="true"></i> {{ __('app.orders.title') }}</div>
<hr>

<div class="containerMain">
	<div class="content table_center">
		<div class="orders-toolbar">
			<a href="{{ route('user.order.new') }}" class="btn btn-brand"><i class="fa fa-plus" aria-hidden="true"></i> {{ __('app.orders.new_order') }}</a>
			@if($hasOrders)
			<a class="mail_user_order_details">
				<button type="button" class="btn btn-brand"><i class="fa fa-envelope" aria-hidden="true"></i> {{ __('app.orders.send_mail') }}</button>
			</a>
			@endif
		</div>
		@if($hasOrders)
		<hr>
		<div class="shop-subtitle">{{ __('app.orders.intro') }}</div>
		<br>

		@php $searching = $search !== ''; @endphp
		<form method="get" action="{{ route('user.ordered-uniform') }}" class="orders-search" role="search">
			<div class="orders-search-field">
				<i class="fa fa-search" aria-hidden="true"></i>
				<input type="text" name="search" id="orderSearch" value="{{ $search }}" class="form-control"
					placeholder="{{ __('app.orders.search_placeholder') }}"
					aria-label="{{ __('app.orders.search_placeholder') }}" autocomplete="off" />
			</div>
			{{-- Month and year are set aside during a search, which spans every
			     month; the hidden fields keep them so Clear returns to them. --}}
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="orderMonthFilter">{{ __('app.orders.month') }}</label>
				<select name="month" id="orderMonthFilter" class="form-control" {{ $searching ? 'disabled' : '' }} onchange="this.form.submit();">
					<option value="all" {{ $month === 'all' ? 'selected' : '' }}>{{ __('app.orders.all_months') }}</option>
					@for($m = 1; $m <= 12; $m++)
					<option value="{{ $m }}" {{ $month === (string) $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(2000, $m, 1)->locale(app()->getLocale())->translatedFormat('F') }}</option>
					@endfor
				</select>
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="orderYearFilter">{{ __('app.orders.year') }}</label>
				<select name="year" id="orderYearFilter" class="form-control" {{ $searching ? 'disabled' : '' }} onchange="this.form.submit();">
					<option value="all" {{ $year === 'all' ? 'selected' : '' }}>{{ __('app.orders.all_years') }}</option>
					@foreach($years as $y)
					<option value="{{ $y }}" {{ $year === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
					@endforeach
				</select>
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="orderStatusFilter">{{ __('app.orders.status') }}</label>
				<select name="status" id="orderStatusFilter" class="form-control" onchange="this.form.submit();">
					<option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('app.orders.all_statuses') }}</option>
					@foreach($statusOptions as $statusKey => $statusOptionLabel)
					<option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>{{ __('app.status.' . $statusKey) }}</option>
					@endforeach
				</select>
			</div>
			@if($searching)
			<input type="hidden" name="month" value="{{ $month }}" />
			<input type="hidden" name="year" value="{{ $year }}" />
			@endif
			@if($searching)
			<a href="{{ route('user.ordered-uniform', ['month' => $month, 'year' => $year, 'status' => $status]) }}" class="btn btn-default"><i class="fa fa-times" aria-hidden="true"></i> {{ __('app.orders.clear') }}</a>
			@endif
		</form>
		@if($searching)
		<div class="orders-search-result">{!! __('app.orders.showing_search', ['search' => '<strong>' . e($search) . '</strong>']) !!}</div>
		@endif
		@endif

		@if($data != 0)

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
						$collectionDate = $order->collection_date ? \Carbon\Carbon::parse($order->collection_date)->locale(app()->getLocale())->translatedFormat('d/m/y h:i A (D)') : null;
						$detailsId = 'order-detail-' . $order->id;
						// Every uniform on the order; one checkout can hold several.
						$uniformLabel = !empty($array['uniformLabel'])
							? $array['uniformLabel']
							: ($uniform ? $uniform->uniform_type . ($uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : '') : '-');
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
						<td data-label="{{ __('app.orders.last_updated') }}">{{ $order->updated_at ? date('d/m/y h:i A', strtotime($order->updated_at)) : '-' }}</td>
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
		@elseif($hasOrders)
		<p class="text-muted">{{ __('app.orders.none_match') }}</p>
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
		// Search as the member types: the list reloads once they pause, so
		// there is no Search button to press. The page reload drops focus, so
		// it is put back in the box with the caret after the text.
		var $orderSearch = $('#orderSearch');
		var searchTimer = null;
		var lastSearch = $.trim($orderSearch.val() || '');

		try {
			if (sessionStorage.getItem('orderSearchFocus') === '1') {
				sessionStorage.removeItem('orderSearchFocus');
				var el = $orderSearch.get(0);
				if (el) {
					el.focus();
					el.setSelectionRange(el.value.length, el.value.length);
				}
			}
		} catch (e) {}

		$orderSearch.on('input', function() {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(function() {
				var value = $.trim($orderSearch.val() || '');
				if (value === lastSearch) {
					return;
				}
				lastSearch = value;
				try { sessionStorage.setItem('orderSearchFocus', '1'); } catch (e) {}
				$orderSearch.closest('form').trigger('submit');
			}, 500);
		});

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
