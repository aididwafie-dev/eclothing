@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
// echo "<pre>";
// print_r($data);
// echo "<pre>";die;
?>

					</br>
					<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> All accessories</div>
					<hr>
					<div class="text-center">
					<a href="{{ url('admin/accessories/add') }}" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> ADD NEW ACCESSORY</a>
</div>
					<div class="content full table-responsive">
					<table id="table" class="display" cellspacing="0" width="100%">
					
						<thead>
							<tr>
							<th>#</th>
								<th>Name of accessory</th>
								<th>Edit</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
							@foreach($accessories as $values)
								<?php
									$stringId = "DCS".$values->id."DCS";
									$accessoryId = base64_encode($stringId);
								?>
								<tr>
								<td></td>
									<td>{{ $values->value }}</td>
									<!--<td><a href="{{ url('accessories/edit/'.$accessoryId) }}"><button><i class="fa fa-edit" aria-hidden="true"></i>Edit</button></a></td>-->
									<td><a href="{{ url('admin/accessories/edit/'.$accessoryId) }}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-edit"></span></a></td>
									<td><a href='javascript:void(0)' class='btn btn-sm btn-danger delete_accessory' data-url="{{url('delete-accessory/'.$accessoryId)}}"><span class='glyphicon glyphicon-trash'></span></a></td>
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
				
						$(document.body).on('click','.delete_accessory',function(){
					var delete_url = $(this).attr('data-url');
					var check = confirm('Are you sure you want to remove this accessory from the site permanently?');
					if(check == true)
					window.location.href = delete_url;
				});
			});
				
			} );
		</script>
	</body>
</html>
