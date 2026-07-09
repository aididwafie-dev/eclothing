@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
	$stringId = "DCS".$data['uniforms'][0]->id."DCS";
	$uniformsId = base64_encode($stringId);
?>

</br>
<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> All Clothes of Uniform <B>{{$data['uniforms'][0]->uniform_type}}</B></div>
<hr>
<div class="text-center">
	<a href="{{ url('uniform/add-cloth/'.$uniformsId) }}" class="btn btn-xs btn-success"><span class="glyphicon glyphicon-plus-sign"></span> ADD NEW CLOTH</a>
</div>
<div class="content full table-responsive">
	<table id="table" class="display" cellspacing="0" width="100%">
		<thead>
			<tr>
				<th>Clothes type</th>
				<th>Clothes size</th>
				<th>Gender</th>
				<th>Rank</th>
				<th>Tred</th>
				<th>Religion</th>
				<th>Edit</th>
				<th>Remove</th>
			</tr>
		</thead>
		<tbody>
			@foreach($data['clothes'] as $clothes)

			<?php
										$stringId = "DCS".$clothes->id."DCS";
										$clothesId = base64_encode($stringId);
									?>
			<tr>
				<td>{{ $clothes->clothes_type }}</td>
				<td>{{ $clothes->clothes_size }}</td>
				<td>{{ isset($clothes->jantina_value) ? $clothes->jantina_value : "-ALL-" }}</td>
				<td>{{ isset($clothes->pangkat_value) ? $clothes->pangkat_value : "-ALL-" }}</td>
				<td>{{ isset($clothes->ketukangan_value) ? $clothes->ketukangan_value : "-ALL-" }}</td>
				<td>{{ $clothes->religion ? $clothes->religion : "-ALL-" }}</td>
				<td><a href="{{ url('uniform/clothes-edit/'.$clothesId) }}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-edit"></span></a></td>
				<td><a class="btn btn-sm btn-danger delete_button" data-id="{{ url('uniform/clothes-delete/'.$clothesId.'/'.$uniformsId) }}"><span class="glyphicon glyphicon-trash"></span></a></td>
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
		var check = confirm('Users may have orderd this Cloth. Are you sure you want to remove this cloth?');
		if (check == true)
			window.location.href = delete_cloth_url;
	});

</script>
</body>

</html>
