@include('static-layout/header')
@include('static-layout/admin_sidebar')

<br>
<div class="title"><i class="fa fa-clipboard" aria-hidden="true"></i> {{ __('app.admin_orders.title') }}</div>
<hr>

<div class="containerMain">
	<div class="content full">
		<div class="shop-subtitle">{{ __('app.admin_orders.subtitle') }}</div>
		<br>

		@php
			$search = isset($search) ? $search : '';
			$statusOptions = isset($statusOptions) ? $statusOptions : [];
			$statusFilter = isset($status) ? $status : 'pending';
			// Translated from the status key so the filter, the badges and the
			// summary line all read in the chosen language.
			$statusFilterLabel = $statusFilter === 'all'
				? __('app.admin_orders.all_statuses')
				: __('app.status.' . (isset($statusOptions[$statusFilter]) ? $statusFilter : 'pending'));
		@endphp
		<form method="get" action="{{ route('admin.uniform-orders') }}" class="orders-search" role="search">
			<div class="orders-search-field">
				<i class="fa fa-search" aria-hidden="true"></i>
				<input type="text" name="search" value="{{ $search }}" class="form-control"
					placeholder="{{ __('app.admin_orders.search_placeholder') }}" inputmode="numeric"
					aria-label="{{ __('app.admin_orders.search_label') }}" autocomplete="off" />
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="orderStatusFilter">{{ __('app.admin_orders.status') }}</label>
				{{-- Disabled during a search because an Order ID search deliberately
				     spans every status; the hidden field keeps the chosen status so
				     Clear returns to it. --}}
				<select name="status" id="orderStatusFilter" class="form-control"
					{{ $search !== '' ? 'disabled' : '' }} onchange="this.form.submit();">
					@foreach($statusOptions as $statusKey => $statusOptionLabel)
					<option value="{{ $statusKey }}" {{ $statusFilter === $statusKey ? 'selected' : '' }}>{{ __('app.status.' . $statusKey) }}</option>
					@endforeach
					<option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ __('app.admin_orders.all_statuses') }}</option>
				</select>
			</div>
			@if($search !== '')
			<input type="hidden" name="status" value="{{ $statusFilter }}" />
			@endif
			<button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> {{ __('app.admin_orders.search') }}</button>
			@if($search !== '')
			<a href="{{ route('admin.uniform-orders', ['status' => $statusFilter]) }}" class="btn btn-default"><i class="fa fa-times" aria-hidden="true"></i> {{ __('app.admin_orders.clear') }}</a>
			@endif
		</form>

		@if($search !== '')
			@if($orders && $orders->count())
			<div class="orders-search-result">{!! __('app.admin_orders.showing_search', ['id' => '<strong>#' . e($search) . '</strong>']) !!}</div>
			@endif
		@else
		<div class="orders-search-result">{!! __('app.admin_orders.showing_status', ['status' => '<strong>' . e($statusFilterLabel) . '</strong>']) !!}</div>
		@endif

		@if($orders && $orders->count())
		<div class="table-responsive">
			<table class="table table-orders table-orders-wide">
				<thead>
					<tr>
						<th>{{ __('app.admin_orders.order') }}</th>
						<th>{{ __('app.admin_orders.service_id') }}</th>
						<th>{{ __('app.admin_orders.name') }}</th>
						<th>{{ __('app.admin_orders.unit') }}</th>
						<th>{{ __('app.admin_orders.uniform') }}</th>
						<th>{{ __('app.admin_orders.items') }}</th>
						<th>{{ __('app.admin_orders.status') }}</th>
						<th>{{ __('app.admin_orders.collection_date') }}</th>
						<th>{{ __('app.admin_orders.ordered_at') }}</th>
						<th>{{ __('app.admin_orders.action') }}</th>
					</tr>
				</thead>
				<tbody>
					@foreach($orders as $order)
					@php
						$orderId = base64_encode('DCS'.$order->id.'DCS');
						$statusClass = !empty($order->status_class) ? $order->status_class : 'status-pending';
						$statusLabel = __('app.status.' . (!empty($order->status_key) ? $order->status_key : 'pending'));
					@endphp
					<tr>
						<td data-label="Order">#{{ $order->id }}</td>
						<td data-label="Service ID">{{ $order->s_id ? $order->s_id : '-' }}</td>
						<td data-label="Name">{{ $order->name ? $order->name : 'N/A' }}</td>
						<td data-label="Unit">{{ $order->unit_name ? $order->unit_name : 'N/A' }}</td>
						<td data-label="Uniform">{{ $order->uniform_type }}{{ $order->uniform_name ? ' (' . $order->uniform_name . ')' : '' }}</td>
						<td data-label="Items">{{ $order->items_count }}</td>
						<td data-label="Status"><span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
						<td data-label="{{ __('app.admin_orders.collection_date') }}">{{ $order->collection_date ? date('d M Y', strtotime($order->collection_date)) : __('app.admin_orders.to_be_updated') }}</td>
						<td data-label="{{ __('app.admin_orders.ordered_at') }}">{{ $order->created_at ? date('d M Y h:i A', strtotime($order->created_at)) : '-' }}</td>
						<td data-label="{{ __('app.admin_orders.action') }}"><a href="{{ route('admin.uniform-orders.show', ['id' => $orderId]) }}" class="btn btn-sm btn-default"><i class="fa fa-eye" aria-hidden="true"></i> {{ __('app.admin_orders.view_detail') }}</a></td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>
		@if($orders->hasPages())
		<div class="order-pagination">
			<a href="{{ $orders->previousPageUrl() ?: 'javascript:void(0)' }}" class="btn btn-sm btn-default{{ $orders->onFirstPage() ? ' disabled' : '' }}">{{ __('app.admin_orders.previous') }}</a>
			<span class="order-pagination-label">{{ __('app.admin_orders.page', ['page' => $orders->currentPage()]) }}</span>
			<a href="{{ $orders->nextPageUrl() ?: 'javascript:void(0)' }}" class="btn btn-sm btn-default{{ $orders->hasMorePages() ? '' : ' disabled' }}">{{ __('app.admin_orders.next') }}</a>
		</div>
		@endif
		@else
		<div class="alert alert-info">
			@if($search !== '')
			{!! __('app.admin_orders.not_found_search', ['id' => '<strong>#' . e($search) . '</strong>']) !!}
			@elseif($statusFilter === 'all')
			{{ __('app.admin_orders.none') }}
			@else
			{!! __('app.admin_orders.none_status', ['status' => '<strong>' . e($statusFilterLabel) . '</strong>']) !!}
			<a href="{{ route('admin.uniform-orders', ['status' => 'all']) }}">{{ __('app.admin_orders.view_all_statuses') }}</a>.
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
