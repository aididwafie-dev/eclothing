@include('static-layout/header')
@include('static-layout/admin_sidebar')

<br>
<div class="title"><i class="fa fa-archive" aria-hidden="true"></i> Personal Inventory</div>
<hr>

<div class="containerMain">
	<div class="content full">
		<div class="shop-subtitle">Uniform items on order for each member. Every member is listed &mdash; nothing on order reads 0.</div>
		<br>

		@php
			$filtered = $search !== '' || $uniformFilter !== 'all' || $monthFilter !== 'all' || $yearFilter !== 'all';
			$periodLabel = $monthFilter === 'all' && $yearFilter === 'all'
				? 'all dates'
				: trim(($monthFilter === 'all' ? '' : $months[(int) $monthFilter] . ' ') . ($yearFilter === 'all' ? 'every year' : $yearFilter));
		@endphp

		<form method="get" action="{{ route('admin.personal-inventory') }}" class="orders-search" role="search">
			<div class="orders-search-field">
				<i class="fa fa-search" aria-hidden="true"></i>
				<input type="text" name="search" value="{{ $search }}" class="form-control"
					placeholder="Search by Service ID (e.g. 601234)" aria-label="Search by Service ID" autocomplete="off" />
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="uniformFilter">Uniform</label>
				<select name="uniform" id="uniformFilter" class="form-control" onchange="this.form.submit();">
					<option value="all">All uniform types</option>
					@foreach($uniformsAll as $uniform)
					<option value="{{ $uniform->id }}" {{ (string) $uniformFilter === (string) $uniform->id ? 'selected' : '' }}>
						{{ $uniform->uniform_type }}{{ $uniform->uniform_name ? ' - ' . $uniform->uniform_name : '' }}
					</option>
					@endforeach
				</select>
			</div>
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
			<button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> Search</button>
			@if($filtered)
			<a href="{{ route('admin.personal-inventory') }}" class="btn btn-default"><i class="fa fa-times" aria-hidden="true"></i> Clear</a>
			@endif
		</form>

		<div class="orders-search-result">
			Showing <strong>{{ $members->total() }}</strong> member(s),
			{{ $uniformFilter === 'all' ? 'all uniform types' : 'uniform ' . $columns->first()->uniform_type }},
			{{ $periodLabel }}@if($search !== ''), Service ID matching <strong>{{ $search }}</strong>@endif.
		</div>

		@if($members->count())
		<div class="table-responsive">
			<table class="table table-orders table-inventory">
				<thead>
					<tr>
						<th>Service ID</th>
						<th>Name</th>
						<th>Unit</th>
						@foreach($columns as $uniform)
						<th class="inventory-head" title="{{ $uniform->uniform_name ?: $uniform->uniform_type }}">{{ $uniform->uniform_type }}</th>
						@endforeach
						<th class="inventory-head">Orders</th>
						<th class="inventory-head">Total</th>
					</tr>
				</thead>
				<tbody>
					@foreach($members as $member)
					@php
						$memberTotals = $totals[$member->id] ?? [];
						$rowOrders = 0;
						$rowItems = 0;
						foreach ($memberTotals as $cell) {
							$rowOrders += $cell['orders'];
							$rowItems += $cell['items'];
						}
					@endphp
					@php
						// The chosen period follows the member into the detail page.
						$detailParams = array_filter([
							'month' => $monthFilter === 'all' ? null : $monthFilter,
							'year' => $yearFilter === 'all' ? null : $yearFilter,
						]);
						$detailUrl = $member->s_id
							? route('admin.personal-inventory.show', array_merge(['sId' => $member->s_id], $detailParams))
							: null;
					@endphp
					<tr class="inventory-row" @if($detailUrl) data-href="{{ $detailUrl }}" @endif>
						<td data-label="Service ID">
							@if($detailUrl)
							<a href="{{ $detailUrl }}" target="_blank" rel="noopener">{{ $member->s_id }}</a>
							@else
							-
							@endif
						</td>
						<td data-label="Name">{{ $member->name ?: 'N/A' }}</td>
						<td data-label="Unit">{{ $member->unit_name ?: 'N/A' }}</td>
						@foreach($columns as $uniform)
						@php $cell = $memberTotals[$uniform->id] ?? ['orders' => 0, 'items' => 0]; @endphp
						<td data-label="{{ $uniform->uniform_type }}"
							class="inventory-cell{{ $cell['items'] ? '' : ' is-zero' }}"
							title="{{ $uniform->uniform_name ?: $uniform->uniform_type }}: {{ $cell['items'] }} item(s) across {{ $cell['orders'] }} order(s)">{{ $cell['items'] }}</td>
						@endforeach
						<td data-label="Orders" class="inventory-cell{{ $rowOrders ? '' : ' is-zero' }}">{{ $rowOrders }}</td>
						<td data-label="Total" class="inventory-cell inventory-total{{ $rowItems ? '' : ' is-zero' }}">{{ $rowItems }}</td>
					</tr>
					@endforeach
				</tbody>
			</table>
		</div>

		@if($members->hasPages())
		<div class="order-pagination">
			<a href="{{ $members->previousPageUrl() ?: 'javascript:void(0)' }}" class="btn btn-sm btn-default{{ $members->onFirstPage() ? ' disabled' : '' }}">Previous</a>
			<span class="order-pagination-label">Page {{ $members->currentPage() }} of {{ $members->lastPage() }}</span>
			<a href="{{ $members->nextPageUrl() ?: 'javascript:void(0)' }}" class="btn btn-sm btn-default{{ $members->hasMorePages() ? '' : ' disabled' }}">Next</a>
		</div>
		@endif
		@else
		<div class="alert alert-info">
			@if($search !== '')
			No member found with a Service ID matching <strong>{{ $search }}</strong>.
			@else
			No members recorded yet.
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
<script type="text/javascript">
	// Clicking anywhere on a row opens that member's full inventory in a new
	// tab. The Service ID cell is a real link, so it handles its own click --
	// and middle-click or ctrl-click still work as expected.
	$(document).on('click', '.inventory-row', function (e) {
		if ($(e.target).closest('a').length) {
			return;
		}

		var href = $(this).data('href');
		if (href) {
			window.open(href, '_blank', 'noopener');
		}
	});
</script>
</body>
</html>
