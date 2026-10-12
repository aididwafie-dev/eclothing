@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-building" aria-hidden="true"></i> All Bases</div>
					<hr>
					<div class="text-center">
					<a href="{{ url('admin/base/add') }}" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> ADD NEW BASE</a>
</div>
					<div class="content full table-responsive">
					<table id="table" class="display" cellspacing="0" width="100%">

						<thead>
							<tr>
							<th>#</th>
								<th>Base</th>
								<th>Org name</th>
								<th>Status</th>
								<th>Units</th>
								<th>Edit</th>
								<th>Delete</th>
							</tr>
						</thead>
						<tbody>
							@foreach($bases as $values)

								<?php
									$stringId = "DCS".$values->id."DCS";
									$baseId = base64_encode($stringId);
								?>
								<tr>
								<td></td>
									<td>{{ $values->base }}</td>
									<td>{{ $values->org_name ?? '' }}</td>
									<td>{{ (int) $values->status === 1 ? 'Active' : 'Inactive' }}</td>
									<td>{{ (int) $values->unit_count }}</td>
									<td><a href="{{ url('admin/base/edit/'.$baseId) }}" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-edit"></span></a></td>
									<td><a href='javascript:void(0)' class='btn btn-sm btn-danger delete_base' data-url="{{url('delete-base/'.$baseId)}}"><span class='glyphicon glyphicon-trash'></span></a></td>
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

						$(document.body).on('click','.delete_base',function(){
					var delete_url = $(this).attr('data-url');
					var check = confirm('Are you sure you want to remove this base from the site permanently? Its units will keep existing but lose this base.');
					if(check == true)
					window.location.href = delete_url;
				});

			});

			} );
		</script>
	</body>
</html>
