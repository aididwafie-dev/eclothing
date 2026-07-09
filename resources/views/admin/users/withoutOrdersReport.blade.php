@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Users without Orders Reports</div>
<hr>
<div class="containerMain">
	<div class="content full table-responsive">
		<a class="back-btn" href="{{ url('/admin/users-report') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
		<br />
		@if(count($users) == 0)
		<B>No users found.</B>
		@else
		<i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report of Users without orders</B>
		<hr>
		<form autocomplete="off" method="post" action="{{ url('/user-report-without-orders') }}" name="order-report" id="order-report">
			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
			<div class="form-group col-sm-4">
				<label class="label_">Filter by unit</label>
				<select class="form-control" id="uniforms_id" name="unit_id">
					<option value="">All units</option>
					@foreach($units as $unit)
					<option value="{{$unit->id}}" <?= isset($_POST['unit_id']) && $_POST['unit_id'] == $unit->id ? 'selected' : ''; ?>>{{$unit->value}}</option>
					@endforeach
				</select>
			</div>

			<div class="subBtn col-sm-4"><br />
				<button class="btn btn-warning" type="submit" id="submit" name="submit">Filter results</button>
			</div>
		</form>
				<a href="{{ url('/creat-excel-users-without-orders') }}">
			<button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
		</a>
			<a href="{{ url('/admin/announcements') }}">
			<button type="button" class="btn"><i class="fa fa-envelope" aria-hidden="true"></i> Send announcement</button>
		</a>
<br />
<br />
		<table class="display table" cellspacing="0" id="usersList">
			<thead>
			<tr>
				<th>Service ID</th>
				<th>Name</th>
				<th>Telephone</th>
				<th>Unit</th>
				<th>Created</th>
				<th>Updated</th>
			</tr>
			</thead>
			<tbody>
			<?php 
			foreach ($users as $user) { ?>
			<tr>
				<td>{{ $user->s_id }}</td>
				<td>{{ $user->name }}</td>
				<td>{{ $user->telephone_number }}</td>
				<td>{{ $user->value }}</td>
				<td>{{ $user->created_at }}</td>
				<td>{{ $user->updated_at }}</td>
			</tr>
			<?php } ?>
			</tbody>
		</table>
		<hr />
		<B>Total users {{ count($users) }}</B>
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