@include('static-layout/header')
@include('static-layout/admin_sidebar')

<br>
<div class="title"><i class="fa fa-archive" aria-hidden="true"></i> Personal Inventory &mdash; {{ $member->s_id }}</div>
<hr>

<div class="containerMain">
	<div class="content full">
		@php
			$periodLabel = $monthFilter === 'all' && $yearFilter === 'all'
				? 'all dates'
				: trim(($monthFilter === 'all' ? '' : $months[(int) $monthFilter] . ' ') . ($yearFilter === 'all' ? 'every year' : $yearFilter));
		@endphp

		<div class="inventory-identity">
			<div>
				<div class="report-card-title">{{ $member->name ?: 'N/A' }}</div>
				<div class="order-card-meta">
					Service ID {{ $member->s_id }}
					@if($member->rank_name) &middot; {{ $member->rank_name }} @endif
					@if($member->unit_name) &middot; {{ $member->unit_name }} @endif
				</div>
			</div>
			<div class="inventory-identity-totals">
				<div class="shop-board-badge"><i class="fa fa-cubes" aria-hidden="true"></i> {{ $itemTotal }} item(s)</div>
				<div class="shop-board-badge"><i class="fa fa-clipboard" aria-hidden="true"></i> {{ $orderTotal }} order(s)</div>
			</div>
		</div>

		<form method="get" action="{{ route('admin.personal-inventory.show', ['sId' => $member->s_id]) }}" class="orders-search">
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="monthFilter">Month</label>
				<select name="month" id="monthFilter" class="form-control" onchange="this.form.submit();">
					<option value="all">All months</option>
					@foreach($months as $monthNumber => $monthLabel)
					<option value="{{ $monthNumber }}" {{ (string) $monthFilter === (string) $monthNumber ? 'selected' : '' }}>{{ $monthLabel }}</option>
					@endforeach
				</select>
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="yearFilter">Year</label>
				<select name="year" id="yearFilter" class="form-control" onchange="this.form.submit();">
					<option value="all">All years</option>
					@foreach($years as $year)
					<option value="{{ $year }}" {{ (string) $yearFilter === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
					@endforeach
				</select>
			</div>
			@if($monthFilter !== 'all' || $yearFilter !== 'all')
			<a href="{{ route('admin.personal-inventory.show', ['sId' => $member->s_id]) }}" class="btn btn-default"><i class="fa fa-times" aria-hidden="true"></i> Clear</a>
			@endif
			<a href="{{ route('admin.personal-inventory') }}" class="btn btn-default"><i class="fa fa-list" aria-hidden="true"></i> Back to list</a>
		</form>

		<div class="orders-search-result">Showing <strong>{{ $periodLabel }}</strong>.</div>

		@forelse($groups as $uniformId => $group)
		<div class="order-card">
			<div class="order-card-header">
				<div>
					<div class="report-card-title">{{ $group['label'] }}</div>
					<div class="order-card-meta">{{ $group['itemCount'] }} item(s) across {{ $group['orderCount'] }} order(s)</div>
				</div>
			</div>

			@foreach($group['orders'] as $entry)
			<div class="inventory-order">
				<div class="inventory-order-head">
					<span class="inventory-order-ref">Order #{{ $entry['order']->id }}</span>
					<span class="status-badge {{ $entry['status']['class'] }}">{{ $entry['status']['label'] }}</span>
					<span class="order-card-meta">
						Ordered {{ $entry['order']->created_at ? date('d M Y', strtotime($entry['order']->created_at)) : '-' }}
						@if($entry['order']->collection_date)
						&middot; Collection {{ date('d M Y', strtotime($entry['order']->collection_date)) }}
						@endif
						&middot; {{ $entry['itemCount'] }} item(s)
					</span>
				</div>

				@if(trim((string) $entry['order']->remarks) !== '')
				<div class="order-card-meta">Remarks: {{ $entry['order']->remarks }}</div>
				@endif

				<div class="table-responsive">
					<table class="table table-orders">
						<thead>
							<tr>
								<th>Clothing Item</th>
								<th>Size</th>
								<th>Quantity</th>
							</tr>
						</thead>
						<tbody>
							@forelse($entry['items'] as $item)
							<tr>
								<td data-label="Clothing Item">{{ $item->clothes }}</td>
								<td data-label="Size">{{ $item->size !== null && $item->size !== '' ? $item->size : '-' }}</td>
								<td data-label="Quantity">{{ max(1, (int) ($item->quantity ?? 1)) }}</td>
							</tr>
							@empty
							<tr>
								<td data-label="Clothing Item" colspan="3">No clothing lines recorded on this order.</td>
							</tr>
							@endforelse
						</tbody>
					</table>
				</div>
			</div>
			@endforeach
		</div>
		@empty
		<div class="alert alert-info">
			No uniform ordered by this member for <strong>{{ $periodLabel }}</strong>. Counted as 0.
		</div>
		@endforelse
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
</body>
</html>
