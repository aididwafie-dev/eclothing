@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> User Reports</div>
<hr>
<div class="containerMain">
<div class="content full table-responsive">
		<a class="back-btn" href="{{ url('/admin/users-report') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
		<br />
		@if(count($users) == 0)

		<B>No users found.</B>

		@else
		<i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report of Users in each unit</B>
		<hr>
			<a href="{{ url('/creat-excel-users-units') }}">
			<button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
		</a><br /><br />
		<table  class="display table" cellspacing="0" id="usersList">
		<thead>
			<tr>
				<th>#</th>
				<th>Unit name</th>
				<th>Users with order</th>
				<th>Users without order</th>
			</tr>
			</thead>
			<tbody>
			<?php 
			$i = 1;	
			foreach ($units as $unit_id => $unit_name) { ?>
			<tr>
				<td><?= $i++; ?></td>
				<td><?= $unit_name; ?></td>
				<td>{{ isset($users[$unit_id]) ? count($users[$unit_id]) : 0 }}</td>
				<td>{{ isset($users[$unit_id]) ? $total[$unit_id] - count($users[$unit_id]) : $total[$unit_id] }}</td>
			</tr>
			<?php } ?>
			</tbody>
		</table>
		<hr />
		@endif
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/colreorder/1.5.2/css/colReorder.bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedheader/3.1.7/css/fixedHeader.dataTables.min.css">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/colreorder/1.5.2/js/dataTables.colReorder.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.1.7/js/dataTables.fixedHeader.min.js"></script>

<script type="text/javascript">
	$(document).ready(function() {

		var dataTable = $('#usersList').DataTable({
			stateSave: true,
			fixedHeader: {
				headerOffset: 52
			},
			colReorder: true,
			order: [
				[2, "desc"]
			]
		});
	});

</script>
</body>

</html>
