@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
// echo "<pre>";
// print_r($data);
// echo "<pre>";die;
?>

					</br>
					<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> All Units</div>
					<hr>
					<div class="text-center">
					<a href="{{ url('admin/unit/add') }}" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> ADD NEW UNIT</a>
</div>
					<div class="content full table-responsive">
					<table id="table" class="display" cellspacing="0" width="100%">
					
						<thead>
							<tr>
							<th>#</th>
								<th>Name of unit</th>
								<th>Edit</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
							@foreach($units as $values)
								<?php
									$stringId = "DCS".$values->id."DCS";
									$unitId = base64_encode($stringId);
								?>
								<tr>
								<td></td>
									<td>{{ $values->value }}</td>
									<!--<td><a href="{{ url('unit/edit/'.$unitId) }}"><button><i class="fa fa-edit" aria-hidden="true"></i>Edit</button></a></td>-->
									<td><a href="{{ url('admin/unit/edit/'.$unitId) }}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-edit"></span></a></td>
									<td><a href='javascript:void(0)' class='btn btn-sm btn-danger delete_unit' data-url="{{url('delete-unit/'.$unitId)}}"><span class='glyphicon glyphicon-trash'></span></a></td>
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
			$(document).ready(function(){
				var t = $('#table').DataTable({ordering:0,stateSave: true});
					t.on( 'order.dt search.dt', function () {
        t.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
            cell.innerHTML = i+1;
        } );
    } ).draw();
					$(document).ready(function(){
				
						$(document.body).on('click','.delete_unit',function(){
					var delete_url = $(this).attr('data-url');
					var check = confirm('Are you sure you want to remove this unit from the site permanently?');
					if(check == true)
					window.location.href = delete_url;
				});
			});
				
			} );
		</script>
	</body>
</html>
