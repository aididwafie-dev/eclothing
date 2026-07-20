@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
// echo "<pre>";
// print_r($data);
// echo "<pre>";die;
?>

</br>
<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> All Uniforms</div>
<hr>
<div class="text-center">
	<a href="{{ url('uniform/add-uniform') }}" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> ADD NEW UNIFORM TYPE</a>
</div>
<div class="content full table-responsive">
	<table id="table" class="display" cellspacing="0" width="100%">

		<thead>
			<tr>
				<th>Uniform type</th>
				<th>Uniform name</th>
				<th>Edit</th>
				<th>Clothes &amp; accessories</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody><?php $i = 1; ?>
			@foreach($uniforms as $values)
			<?php
									$stringId = "DCS".$values->id."DCS";
									$uniformId = base64_encode($stringId);
								?>
			<tr>
				<td>{{ $values->uniform_type }} <input type="hidden" value="<?= sprintf('%03d', preg_replace("/^([0-9]+).*/", "$1", $values->uniform_type));?>" /></td>
				<td>{{ $values->uniform_name }}</td>
				<!--<td><a href="{{ url('uniform/edit-name/'.$uniformId) }}"><button><i class="fa fa-edit" aria-hidden="true"></i>Edit</button></a></td>-->
				<td><a href="{{ url('uniform/edit-name/'.$uniformId) }}" class="btn btn-sm btn-primary"><i class="glyphicon glyphicon-edit"></i></a></td>
				<!--<td><a href="{{ url('/admin/clothes/'.$uniformId) }}"><button><i class="fa fa-folder-open" aria-hidden="true">Show</i></button></a></td>-->
				<td><a href="{{ url('/admin/clothes/'.$uniformId) }}" class="btn btn-sm btn-info"><i class="glyphicon glyphicon-folder-open"></i> &nbsp;Clothes</a>
					<a href="{{ url('/admin/accessories/'.$uniformId) }}" class="btn btn-sm btn-info"><i class="glyphicon glyphicon-paperclip"></i> &nbsp;Accessories</a>
				</td>
				@if ($values->active)
				<td><a href="javascript:void(0)" class="block_unblock btn btn-sm btn-warning" data-url="<?= url("uniform_enable_disable/" .$uniformId); ?>"><i class="fa fa-lock" aria-hidden="true"></i> Disable</a></td>
				@else
				<td><a href="javascript:void(0)" class="block_unblock btn btn-sm btn-primary" data-url="<?= url("uniform_enable_disable/" .$uniformId); ?>"><i class="fa fa-unlock" aria-hidden="true"></i> Enable</a></td>
				@endif
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
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		var t = $('#table').DataTable({
			ordering: true,
			stateSave: true,
			initComplete: function(settings) {
				 table = settings.oInstance.api();
				table
    .order( [ 1, 'asc' ] )
    .draw();
			},
			columns: [
{ "orderDataType": "dom-text", type: 'string' },
				null,
				null,
				null,
				null
        ]
		});
		/*
		t.on('order.dt search.dt', function() {
			t.column(0, {
				search: 'applied',
				order: 'applied'
			}).nodes().each(function(cell, i) {
				cell.innerHTML = i + 1;
			});
		}).draw();
*/
		$(document.body).on('click', '.block_unblock', function() {
			var block_url = $(this).attr('data-url');
			var check = confirm('Are you sure?');
			if (check == true)
				window.location.href = block_url;
		});

		$.fn.dataTable.ext.order['dom-text'] = function  ( settings, col )
{
    return this.api().column( col, {order:'index'} ).nodes().map( function ( td, i ) {
        return $('input', td).val();
    } );
}
 
/* Create an array with the values of all the input boxes in a column, parsed as numbers */
$.fn.dataTable.ext.order['dom-text-numeric'] = function  ( settings, col )
{
    return this.api().column( col, {order:'index'} ).nodes().map( function ( td, i ) {
        return $('input', td).val() * 1;
    } );
}

	});

</script>
</body>

</html>
