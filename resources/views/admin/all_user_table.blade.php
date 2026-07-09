@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-users" aria-hidden="true"></i> All Users</div>
<hr>
<div class="form-group" style="max-width:320px">
	<select class="form-control" id="filter">
		<option value="">All</option>
		<option value="incomplete_rank">Incomplete: Rank</option>
		<option value="incomplete_name">Incomplete: Name</option>
		<option value="incomplete_unit">Incomplete: Unit</option>
		<option value="incomplete_personal">Incomplete: Personal Detail</option>
		<option value="incomplete_uniform">Incomplete: Uniform Detail</option>
		<option value="complete_all">Complete: All Details</option>
	</select>
</div>
<div class="content full table-responsive">
	<table id="userlist" class="display table" cellspacing="0" width="100%">
		<thead>
			<tr>
				<th class="no-sort">#</th>
				<th>Service ID</th>
				<th>Rank</th>
				<th>Name</th>
				<th>Unit</th>
				<th>Basic Details</th>
				<th>Personal Details</th>
				<th>Uniform Details</th>
				<th>Status</th>
				<th>Delete</th>
				<th>Created</th>
				<th>Updated</th>
				<th>Re-send mail</th>
			</tr>
		</thead>
	</table>
	<a href="javascript:void(0)" data-url="<?= url("block_all_users"); ?>" class="block_all btn btn-sm btn-warning"><i class="fa fa-lock" aria-hidden="true"></i> Block all users</a>
	<a href="javascript:void(0)" data-url="<?= url("unblock_all_users"); ?>" class="unblock_all btn btn-sm btn-success"><i class="fa fa-lock" aria-hidden="true"></i> Unblock all users</a>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/colreorder/1.5.2/css/colReorder.bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/fixedheader/3.1.7/css/fixedHeader.dataTables.min.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/colreorder/1.5.2/js/dataTables.colReorder.min.js"></script>
<script src="https://cdn.datatables.net/fixedheader/3.1.7/js/dataTables.fixedHeader.min.js"></script>

<script type="text/javascript">
	$(document).ready(function() {

		var dataTable = $('#userlist').DataTable({
			processing: true,
			stateSave: true,
			 fixedHeader: {
        headerOffset: 52
    },
			colReorder: true,
			order: [
				[10, "desc"]
			],

			"serverSide": true,
			"ajax": {
				url: "/ajax-usersTable",
				type: "post",
				headers: {
					'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
				},
				data: function(d) {
					d.filter = $('#filter').val();
				},
				error: function() {
					$(".employee-grid-error").html("");
					$("#userlist").append('<tbody class="table-error"><tr><th colspan="7">No data found in the server</th></tr></tbody>');
					$("#table_processing").css("display", "none");
				}
			}
		});
		$('#filter').on('change', function() { dataTable.ajax.reload(); });

		$(document.body).on('click', '.block_unblock', function() {
			var block_url = $(this).attr('data-url');
			var check = confirm('Are you sure?');
			if (check == true)
				window.location.href = block_url;
		});

		$(document.body).on('click', '.delete_user', function() {
			var delete_url = $(this).attr('data-url');
			var check = confirm('Are you sure you want to remove this user from the site permanently?');
			if (check == true)
				window.location.href = delete_url;
		});
		$(".block_all").click(function() {
			var check = confirm('Are you sure you want to block all users from the site permanently? Do note, you will lose records of which users actually were unblocked before this.');
			if (check == true)
				window.location.href = $(this).attr('data-url');
		});
		$(".unblock_all").click(function() {
			var check = confirm('Are you sure you want to unblock all users from the site permanently? Do note, you will lose records of which users actually were blocked before this.');
			if (check == true)
				window.location.href = $(this).attr('data-url');
		});

		$(document.body).on('click', '.resend_mail', function() {
			showAppPopup('Sending mail....', 'info', { title: 'Please Wait', autoClose: false });
			var userId = $(this).attr('data-userId');
			$.ajaxSetup({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
				}
			});
			$.ajax({
				type: 'post',
				url: '/ajax-resend-mail',
				data: {
					userId: userId
				},
				success: function(result) {
					showAppPopup(result, 'success');
				}
			});
		});
	});

</script>
</body>

</html>
