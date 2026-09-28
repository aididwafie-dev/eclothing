@include('static-layout/header')
@include('static-layout/admin_sidebar')

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

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
		@endphp
		{{-- Drives the DataTable below rather than submitting: typing searches,
		     changing the status reloads the rows. --}}
		<form class="orders-search" role="search" onsubmit="return false;">
			<div class="orders-search-field">
				<i class="fa fa-search" aria-hidden="true"></i>
				<input type="text" id="orderSearch" value="{{ $search }}" class="form-control"
					placeholder="{{ __('app.admin_orders.search_placeholder') }}"
					aria-label="{{ __('app.admin_orders.search_label') }}" autocomplete="off" />
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="orderStatusFilter">{{ __('app.admin_orders.status') }}</label>
				{{-- Set aside during a search, which spans every status so an
				     order that has moved on is still found. --}}
				<select id="orderStatusFilter" class="form-control" {{ $search !== '' ? 'disabled' : '' }}>
					@foreach($statusOptions as $statusKey => $statusOptionLabel)
					<option value="{{ $statusKey }}" {{ $statusFilter === $statusKey ? 'selected' : '' }}>{{ __('app.status.' . $statusKey) }}</option>
					@endforeach
					<option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>{{ __('app.admin_orders.all_statuses') }}</option>
				</select>
			</div>
		</form>
		<div class="orders-search-result" id="ordersSummary"></div>

		<div class="table-responsive">
			<table class="table table-orders table-orders-wide" id="uniformOrdersTable" style="width:100%">
				<thead>
					<tr>
						<th>{{ __('app.admin_orders.order') }}</th>
						<th>{{ __('app.admin_orders.service_id') }}</th>
						<th>{{ __('app.admin_orders.name') }}</th>
						<th>{{ __('app.admin_orders.unit') }}</th>
						<th>{{ __('app.admin_orders.uniform') }}</th>
						<th>{{ __('app.admin_orders.items') }}</th>
						<th>{{ __('app.admin_orders.status') }}</th>
						<th>{{ __('app.admin_orders.ordered_at') }}</th>
						<th>{{ __('app.admin_orders.last_updated') }}</th>
						<th>{{ __('app.admin_orders.action') }}</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->

{{-- jQuery and Bootstrap already come from the header; loading jQuery again
     here would drop Bootstrap's plugins, which the popup alerts rely on. --}}
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var $search = $('#orderSearch');
		var $status = $('#orderStatusFilter');
		var $summary = $('#ordersSummary');
		var text = {!! json_encode([
			'showingStatus' => __('app.admin_orders.showing_status'),
			'showingSearch' => __('app.admin_orders.showing_search_any'),
			'allStatuses' => __('app.admin_orders.all_statuses'),
			'none' => __('app.admin_orders.none'),
			'noneMatch' => __('app.admin_orders.none_match'),
		]) !!};

		function escapeHtml(value) {
			return $('<div>').text(value).html();
		}

		function summary() {
			var term = $.trim($search.val() || '');
			var html = term !== ''
				? text.showingSearch.replace(':search', '<strong>' + escapeHtml(term) + '</strong>')
				: text.showingStatus.replace(':status', '<strong>' + escapeHtml($status.find('option:selected').text()) + '</strong>');
			$summary.html(html);
		}

		// Keeps the filter in the address bar, so a reload or the Back link from
		// an order's detail page lands on the same list.
		function rememberFilter() {
			try {
				var url = new URL(window.location.href);
				url.searchParams.set('status', $status.val());
				var term = $.trim($search.val() || '');
				if (term !== '') { url.searchParams.set('search', term); } else { url.searchParams.delete('search'); }
				window.history.replaceState(null, '', url.toString());
			} catch (e) {}
		}

		var table = $('#uniformOrdersTable').DataTable({
			processing: true,
			serverSide: true,
			searching: true,
			// No built-in search box: the one above the table drives it.
			dom: 'lrtip',
			pageLength: 25,
			lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
			order: [[8, 'desc']],
			search: { search: $.trim($search.val() || '') },
			columnDefs: [
				{ targets: 9, orderable: false },
				// The mobile card layout labels each cell from its header.
				{ targets: '_all', createdCell: function(td, cellData, rowData, row, col) {
					$(td).attr('data-label', $('#uniformOrdersTable thead th').eq(col).text());
				} }
			],
			ajax: {
				url: {!! json_encode(route('admin.uniform-orders.data')) !!},
				type: 'post',
				headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
				data: function(d) { d.status = $status.val(); },
				error: function() {
					if (window.showAppPopup) {
						window.showAppPopup('Orders could not be loaded. Please refresh the page.', 'danger');
					}
					$('#uniformOrdersTable_processing').hide();
				}
			},
			language: {
				processing: '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>',
				lengthMenu: {!! json_encode(__('app.admin_orders.dt_length')) !!},
				info: {!! json_encode(__('app.admin_orders.dt_info')) !!},
				infoEmpty: {!! json_encode(__('app.admin_orders.dt_info_empty')) !!},
				infoFiltered: '',
				emptyTable: text.none,
				zeroRecords: text.noneMatch,
				paginate: {
					previous: {!! json_encode(__('app.admin_orders.previous')) !!},
					next: {!! json_encode(__('app.admin_orders.next')) !!}
				}
			}
		});

		summary();

		var searchTimer = null;
		$search.on('input', function() {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(function() {
				var term = $.trim($search.val() || '');
				$status.prop('disabled', term !== '');
				summary();
				rememberFilter();
				table.search(term).draw();
			}, 400);
		});

		$status.on('change', function() {
			summary();
			rememberFilter();
			table.ajax.reload();
		});
	});
</script>
</body>
</html>
