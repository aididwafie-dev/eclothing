@include('static-layout/not-logged-header')

<div class="banner">
	<div class="content content-log">
		@if($data == 1)
			<h2 class="text-center">Your account is activated successfully. You are being redirected to the login page.</h2>
		@else
			<h2 class="text-center">Sorry! The URL you clicked does not exist. Please try again.</h2>
		@endif
	</div>
</div>

<script>
	var active = <?php echo $data ?>;
	if (active == 1) {
		window.setTimeout(function() {
			window.location.href = '{{url('/home')}}';
		}, 3000);
	}

</script>
</body>

</html>
