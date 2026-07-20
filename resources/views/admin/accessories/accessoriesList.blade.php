@include('static-layout/header')
@include('static-layout/admin_sidebar')
<?php
$stringId = "DCS".$uniform->id."DCS";
$uniformId = base64_encode($stringId);
?>

</br>
<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> All accessories of Uniform <B>{{$uniform->uniform_type}}</B></div>
<hr>
<div class="text-center">
	<a href="{{ url('accessories/add-accessory/' . $uniformId) }}" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> ADD NEW ACCESSORY</a>
</div>
<div class="content full table-responsive">
	<table id="table" class="display" cellspacing="0" width="100%">
		<thead>
			<tr>
				<th>Accessory name</th>
				<th>Options</th>
				<th>Gender</th>
				<th>Rank</th>
				<th>Tred</th>
				<th>Religion</th>
				<th>Edit</th>
				<th>Remove</th>
			</tr>
		</thead>
		<tbody>
			@foreach($accessories as $clothes)

			<?php
			$stringId = "DCS".$clothes->uniform_cloth_id."DCS";
			$clothesId = base64_encode($stringId);
			?>
			
			<tr>
				<td>{{ $clothes->clothes_type }}</td>
				<td>{{ $clothes->clothes_size }}</td>
				<td>{{ $clothes->jantina_value ? $clothes->jantina_value : "-ALL-" }}</td>
				<td>{{ $clothes->pangkat_value ? $clothes->pangkat_value : "-ALL-" }}</td>
				<td>{{ $clothes->ketukangan_value ? $clothes->ketukangan_value : "-ALL-" }}</td>
				<td>{{ $clothes->religion ? $clothes->religion : "-ALL-" }}</td>
				<td><a href="{{ url('accessories/edit/'.$clothesId) }}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-edit"></span></a></td>
				<td><a class="btn btn-sm btn-danger delete_button" data-id="{{ url('accessories/delete/'.$clothesId) }}"><span class="glyphicon glyphicon-trash"></span></a></td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {

		$('#table').DataTable({
			stateSave: true
		});
	});
	$(document.body).on('click', '.delete_button', function() {
		var delete_cloth_url = $(this).attr('data-id');
		var check = confirm('Users may have ordered this accessory. Are you sure you want to remove this accessory?');
		if (check == true)
			window.location.href = delete_cloth_url;
	});

</script>
</body>

</html>
