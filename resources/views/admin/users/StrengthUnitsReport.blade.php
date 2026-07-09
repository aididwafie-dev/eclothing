@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Users strength by Unit</div>
<hr>
<div class="containerMain">
	<div class="content table_center">
		<a class="back-btn" href="{{ url('/admin/users-report') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
		<br />
		@if(count($totals) == 0)
		<B>No users found.</B>
		@else
		<i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report of Users strength</B>
		<hr>
				<a href="{{ url('/creat-excel-users-strength-units') }}">
			<button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
		</a>
<br />
<br />
		<table class="table" style="">
			<tr>
				<th rowspan="2">Unit</th>
				<th colspan="2" class="text-center">Officer</th>
				<th rowspan="2">Total</th>
				<th colspan="2" class="text-center">Other rank</th>
				<th rowspan="2">Total</th>
				<th rowspan="2">Overall</th>
			</tr>
			<tr>
				<th>Men</th>
				<th>Women</th>
				<th>Men</th>
				<th>Women</th>
			</tr>
			<?php $overall_count = 0;
			foreach ($totals as $total) { $overall_count = $overall_count + $total['man_officer'] + $total['woman_officer'] + $total['man_other'] + $total['woman_other']; ?>
			<tr>
				<td>{{ $total['name'] }}</td>
				<td>{{ $total['man_officer'] }}</td>
				<td>{{ $total['woman_officer'] }}</td>
				<td>{{ $total['man_officer'] + $total['woman_officer'] }}</td>
				<td>{{ $total['man_other'] }}</td>
				<td>{{ $total['woman_other'] }}</td>
				<td>{{ $total['man_other'] + $total['woman_other'] }}</td>
				<td>{{ $total['man_officer'] + $total['woman_officer'] + $total['man_other'] + $total['woman_other'] }}</td>

			</tr>
			<?php } ?>
		</table>
		<hr />
		<B>Total users {{ $overall_count }}</B>
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
