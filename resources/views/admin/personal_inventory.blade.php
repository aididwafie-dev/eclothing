@include('static-layout/header')
@include('static-layout/admin_sidebar')

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

<br>
<div class="title"><i class="fa fa-archive" aria-hidden="true"></i> Personal Inventory</div>
<hr>

<div class="containerMain">
	<div class="content full">
		<div class="shop-subtitle">Uniform items on order for each member. Every member is listed &mdash; nothing on order reads 0.</div>
		<br>

		{{-- Drives the DataTable below rather than submitting. Search, month and
		     year reload the rows; the uniform decides which columns the table
		     has, so changing it reloads the page. --}}
		<form class="orders-search" role="search" onsubmit="return false;">
			<div class="orders-search-field">
				<i class="fa fa-search" aria-hidden="true"></i>
				<input type="text" id="inventorySearch" value="{{ $search }}" class="form-control"
					placeholder="Service ID, name or unit" aria-label="Search members" autocomplete="off" />
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="uniformFilter">Uniform</label>
				<select id="uniformFilter" class="form-control">
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
				<select id="monthFilter" class="form-control">
					<option value="all">All months</option>
					@foreach($months as $monthNumber => $monthLabel)
					<option value="{{ $monthNumber }}" {{ (string) $monthFilter === (string) $monthNumber ? 'selected' : '' }}>{{ $monthLabel }}</option>
					@endforeach
				</select>
			</div>
			<div class="orders-filter-field">
				<label class="orders-filter-label" for="yearFilter">Year</label>
				<select id="yearFilter" class="form-control">
					<option value="all">All years</option>
					@foreach($years as $year)
					<option value="{{ $year }}" {{ (string) $yearFilter === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
					@endforeach
				</select>
			</div>
			<a href="{{ route('admin.personal-inventory') }}" class="btn btn-default" id="inventoryClear" style="display:none;"><i class="fa fa-times" aria-hidden="true"></i> Clear</a>
		</form>

		<div class="orders-search-result" id="inventorySummary"></div>

		<div class="table-responsive">
			<table class="table table-orders table-inventory" id="inventoryTable" style="width:100%">
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
		var $search = $('#inventorySearch');
		var $uniform = $('#uniformFilter');
		var $month = $('#monthFilter');
		var $year = $('#yearFilter');
		var $summary = $('#inventorySummary');
		var $clear = $('#inventoryClear');
		var $headers = $('#inventoryTable thead th');
		var countFrom = 3;
		var lastColumn = $headers.length - 1;

		function escapeHtml(value) {
			return $('<div>').text(value).html();
		}

		function currentFilter() {
			return {
				search: $.trim($search.val() || ''),
				uniform: $uniform.val(),
				month: $month.val(),
				year: $year.val()
			};
		}

		// Keeps the filter in the address bar, so a reload lands on the same list.
		function rememberFilter() {
			var f = currentFilter();
			$clear.toggle(f.search !== '' || f.uniform !== 'all' || f.month !== 'all' || f.year !== 'all');
			try {
				var url = new URL(window.location.href);
				['search', 'uniform', 'month', 'year'].forEach(function(key) {
					if (f[key] && f[key] !== 'all') { url.searchParams.set(key, f[key]); } else { url.searchParams.delete(key); }
				});
				window.history.replaceState(null, '', url.toString());
			} catch (e) {}
		}

		function summary(total) {
			var f = currentFilter();
			var period = f.month === 'all' && f.year === 'all'
				? 'all dates'
				: $.trim((f.month === 'all' ? '' : $month.find('option:selected').text() + ' ') + (f.year === 'all' ? 'every year' : f.year));
			var uniform = f.uniform === 'all' ? 'all uniform types' : 'uniform ' + $.trim($uniform.find('option:selected').text());
			$summary.html('Showing <strong>' + total + '</strong> member(s), ' + escapeHtml(uniform) + ', ' + escapeHtml(period)
				+ (f.search !== '' ? ', matching <strong>' + escapeHtml(f.search) + '</strong>' : '') + '.');
		}

		var table = $('#inventoryTable').DataTable({
			processing: true,
			serverSide: true,
			searching: true,
			// No built-in search box: the one above the table drives it.
			dom: 'lrtip',
			pageLength: {{ (int) $perPage }},
			lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
			order: [[0, 'asc']],
			search: { search: currentFilter().search },
			// Where definitions overlap the earlier one wins: text columns sort
			// A-Z first, the counts largest first.
			columnDefs: [
				{ targets: [0, 1, 2], orderSequence: ['asc', 'desc'] },
				{ targets: '_all', orderSequence: ['desc', 'asc'] },
				{ targets: '_all', createdCell: function(td, cellData, rowData, row, col) {
					// The mobile card layout labels each cell from its header.
					$(td).attr('data-label', $headers.eq(col).text());
					if (col >= countFrom) {
						$(td).addClass('inventory-cell');
						if ($.trim($(td).text()) === '0') { $(td).addClass('is-zero'); }
						if (col === lastColumn) { $(td).addClass('inventory-total'); }
					}
				} }
			],
			ajax: {
				url: {!! json_encode(route('admin.personal-inventory.data')) !!},
				type: 'post',
				headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
				data: function(d) {
					var f = currentFilter();
					d.uniform = f.uniform;
					d.month = f.month;
					d.year = f.year;
				},
				error: function() {
					if (window.showAppPopup) {
						window.showAppPopup('Personal inventory could not be loaded. Please refresh the page.', 'danger');
					}
					$('#inventoryTable_processing').hide();
				}
			},
			language: {
				processing: '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>',
				lengthMenu: 'Show _MENU_ members',
				info: 'Showing _START_ to _END_ of _TOTAL_ members',
				infoEmpty: 'No members to show',
				infoFiltered: '',
				emptyTable: 'No members recorded yet.',
				zeroRecords: 'No member matches.'
			}
		});

		table.on('xhr.dt', function(e, settings, json) {
			if (json) { summary(json.recordsFiltered); }
		});
		rememberFilter();

		var searchTimer = null;
		$search.on('input', function() {
			clearTimeout(searchTimer);
			searchTimer = setTimeout(function() {
				rememberFilter();
				table.search(currentFilter().search).draw();
			}, 400);
		});

		$month.add($year).on('change', function() {
			rememberFilter();
			table.ajax.reload();
		});

		// The uniform decides which columns exist, so the page is rebuilt for it.
		$uniform.on('change', function() {
			rememberFilter();
			window.location.reload();
		});

		// Clicking anywhere on a row opens that member's full inventory in a new
		// tab. The Service ID cell is a real link, so it handles its own click --
		// and middle-click or ctrl-click still work as expected.
		$(document).on('click', '#inventoryTable tbody tr.inventory-row', function(e) {
			if ($(e.target).closest('a').length) {
				return;
			}
			var href = $(this).attr('data-href');
			if (href) {
				window.open(href, '_blank', 'noopener');
			}
		});
	});
</script>
</body>
</html>
