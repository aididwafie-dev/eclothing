@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> All Sizes</div>
					<hr>
					<div class="text-center">
					<a href="{{ url('admin/size/add') }}" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> ADD NEW SIZE</a>
</div>
					<div class="content full table-responsive">
					<table id="table" class="display" cellspacing="0" width="100%">
					
						<thead>
							<tr>
							<th>#</th>
								<th>Size name</th>
								<th>Edit</th>
							</tr>
						</thead>
						<tbody>
							@foreach($sizes as $values)

								<?php
									$stringId = "DCS".$values->id."DCS";
									$sizeId = base64_encode($stringId);
								?>
								<tr>
								<td></td>
									<td>{{ $values->value }}</td>
									<!--<td><a href="{{ url('size/edit/'.$sizeId) }}"><button><i class="fa fa-edit" aria-hidden="true"></i>Edit</button></a></td>-->
									<td><a href="{{ url('admin/size/edit/'.$sizeId) }}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-edit"></span></a></td>
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
				var t = $('#table').DataTable({  
					ordering: false,
					stateSave: true
});
					t.on( 'order.dt search.dt', function () {
        t.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
            cell.innerHTML = i+1;
        } );
    } ).draw();
					$(document).ready(function(){
				
			});
				
			} );
		</script>
	</body>
</html>
