<?php
	$current_url = Request::url();
?>

		<div class="bd-sidebar pull-left" id="userSidebar" aria-hidden="false">
			<nav class="collapse bd-links">

				<div class="sidebar-brand">
					<img src="{{ $siteLogoUrl ?? asset('front_end/images/logo.png') }}" class="sidebar-logo" alt="logo" />
					<div class="sidebar-brand-title">{{ $siteTitle ?? 'PLAS' }}</div>
				</div>

				<a href="#" class="btn btn-link btn-pnl btn-block sidebar-close"><i class="fa fa-times" aria-hidden="true"></i> Close</a>

				<a href="{{ url('/user/personal-details') }}" class="btn btn-link btn-pnl btn-block <?php if (Request::is('user/personal-details')){ echo 'active'; } ?>"><i class="fa fa-address-card-o" aria-hidden="true"></i> Personal Details
				</a>

				<a href="{{ url('/user/uniform-selection') }}" id="uniform-selection" class="btn btn-link btn-pnl btn-block <?php if (Request::is('user/uniform-selection')){ echo 'active'; } ?>"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> Order Uniform</a>

				<a href="{{ url('/user/ordered-uniform') }}" id="uniform-ordered" class="btn btn-pnl btn-block btn-link <?php if (Request::is('user/ordered-uniform')){ echo 'active'; } ?>"><i class="fa fa-shopping-bag" aria-hidden="true"></i> Uniform Ordered</a>

				<a href="{{ url('/user/change-email') }}" class="btn btn-link btn-pnl btn-block <?php if (Request::is('user/change-email')){ echo 'active'; } ?>"><i class="fa fa-envelope-square" aria-hidden="true"></i> Change Email Id</a>

				<a href="{{ url('/user/change-password') }}" class="btn btn-link btn-pnl btn-block <?php if (Request::is('user/change-password')){ echo 'active'; } ?>"><i class="fa fa-lock" aria-hidden="true"></i> Change Password</a>

				<a id="logout" type="button" class="btn btn-link btn-pnl btn-block"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout
				</a>
			</nav>				
		</div>

<script type="text/javascript">
		var enable = '<?php echo (!empty($userDetails) && isset($userDetails->profile_status)) ? $userDetails->profile_status : 0; ?>';
		if(enable == 0) {
			$('#uniform-selection').prop('disabled', true);
			$('#uniform-ordered').prop('disabled', true);
		}
	$(document).ready(function() {
		$("#logout").click(function() {
			var r = confirm("Are you sure you would like to logout? All unsaved changes will be lost.");
			if (r== true) {
				window.location = '{{ url('/user/logout') }}';
			}
		})
	})
</script>

		<div class="rightPanel app-main">
