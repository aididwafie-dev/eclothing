@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-user-circle" aria-hidden="true"></i> All Admins</div>
<hr>
<div class="content full table-responsive">
	<table id="table" class="display table" cellspacing="0" width="100%">
		<thead>
			<tr>
				<th>ID</th>
				<th>Name</th>
				<th>Email ID</th>
				<th>Service ID</th>
				<th>Status</th>
				<th>Delete</th>
			</tr>
		</thead>
	</table>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/colreorder/1.5.2/css/colReorder.bootstrap.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/colreorder/1.5.2/js/dataTables.colReorder.min.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		var dataTable = $('#table').DataTable({
			"processing": true,
			stateSave: true,
			colReorder: true,
			"serverSide": true,
			"ajax": {
				url: "/ajax-admin-list",
				type: "post",
				headers: {
					'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
				},
				error: function() {
					$(".employee-grid-error").html("");
					$("#table").append('<tbody class="table-error"><tr><th colspan="7">No data found in the server</th></tr></tbody>');
					$("#table_processing").css("display", "none");
				}
			}
		});

		$(document.body).on('click', '.admin_active', function() {
			var block_admin_url = $(this).attr('data-url');
			var check = confirm('Are you sure?');
			if (check == true)
				window.location.href = block_admin_url;
		});

		$(document.body).on('click', '.delete_admin', function() {
			var delete_admin_url = $(this).attr('data-url');
			var check = confirm('Are you sure you want to remove this user as an admin?');
			if (check == true)
				window.location.href = delete_admin_url;
		});

	});

</script>
</body>

</html>
