@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-envelope-o" aria-hidden="true"></i> Send announcement to user(s)</div>
<hr>
<div class="containerMain">
	<div class="content">
		<div class="title"><i class="fa fa-envelope" aria-hidden="true"></i> Send announcement:</div>
		<hr>

		<form autocomplete="off" method="post" action="{{ url('/send-announcements') }}" name="basic-details-form" id="basic-details-form">

			<input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">



			<div class="form-group">
				<label class="label_">From:</label>
				<input class="form-control" type="email" id="from" name="from" placeholder="Announcement from email" value="no-reply@rmafplas.com" required />
			</div>

			<div class="form-group">
				<label class="label_">Subject:</label>
				<input class="form-control" type="text" id="subject" name="subject" placeholder="Announcement email subject" required />
			</div>

			<div class="form-group">
				<label class="label_">Message:</label>
				<textarea class="form-control" id="body" name="body" rows="10" placeholder="Announcement email body" required></textarea>
			</div>

			<label class="radio-inline">
				<input type="radio" name="send_to" value="unconfirmed" required /><label class="label_ text-uppercase">Unconfirmed users</label>
			</label>
			<label class="radio-inline">
				<input type="radio" name="send_to" value="confirmed" required /><label class="label_ text-uppercase">Confirmed users</label>
			</label>
			<label class="radio-inline">
				<input type="radio" name="send_to" value="all" required /><label class="label_ text-uppercase">All users</label>
			</label>

			<label class="radio-inline">
				<input type="radio" name="send_to" value="no_orders" required /><label class="label_ text-uppercase">Users without orders <a href="{{ url('/user-report-without-orders') }}"><i class="fa fa-question-circle"></i></a></label>
			</label>

			<div class="subBtn">
				<input class="btn btn-default text-uppercase" type="submit" value="Send announcement" id="submit" name="submit" />
				<a class="btn btn-default" href="{{ url('/admin-cancel') }}">CANCEL</a>
			</div>
			<br />
			<div class="alert alert-danger top-20"><strong>ATTENTION!</strong> Please be careful and double check before you click the 'Send announcement' button. The emails will be sent out instantly.</div>
		</form>
	</div>
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
				error: function() {
					$(".employee-grid-error").html("");
					$("#userlist").append('<tbody class="table-error"><tr><th colspan="7">No data found in the server</th></tr></tbody>');
					$("#table_processing").css("display", "none");
				}
			}
		});

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

		$("#result_mail").hide();
		$(document.body).on('click', '.resend_mail', function() {
			$('#result_mail').show();
			$('#result_mail').html('<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i> Sending mail....');
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
					$('#result_mail').html(result);
					setTimeout(function() {
						$("#result_mail").hide();
					}, 4000);
				}
			});
		});
	});

</script>
</body>

</html>
