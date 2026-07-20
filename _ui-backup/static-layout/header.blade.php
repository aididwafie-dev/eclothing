<html>
<head>
	<title>{{ $siteTitle ?? 'PLAS' }}</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">

	<meta name="_token" content="{{ csrf_token() }}" />

	<link rel="stylesheet" href="{{ asset('front_end/bootstrap-3.3.7-dist/css/bootstrap.min.css') }}">
	<link rel="stylesheet" href="{{ asset('front_end/font-awesome-4.7.0/css/font-awesome.min.css') }}">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link href="{{ asset('front_end/css/style.css') }}" rel="stylesheet" />
	<!-- jQuery library -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<!-- Latest compiled JavaScript -->
	<script src="{{ asset('front_end/bootstrap-3.3.7-dist/js/bootstrap.min.js') }}"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			var mobileBreakpoint = 820;

			function isMobileLayout() {
				return window.innerWidth <= mobileBreakpoint;
			}

			function openSidebar() {
				$(".bd-sidebar").addClass("show");
				$("body").addClass("sidebar-open");
				$(".navbar-togglee").attr("aria-expanded", "true");
			}

			function closeSidebar() {
				$(".bd-sidebar").removeClass("show");
				$("body").removeClass("sidebar-open");
				$(".navbar-togglee").attr("aria-expanded", "false");
			}

			$(".navbar-togglee").click(function() {
				if ($(".bd-sidebar").hasClass("show")) {
					closeSidebar();
				} else {
					openSidebar();
				}
			});

			$(document).on("click", ".sidebar-backdrop, .sidebar-close", function(e) {
				e.preventDefault();
				closeSidebar();
			});

			$(document).on("keydown", function(e) {
				if (e.key === "Escape") {
					closeSidebar();
				}
			});

			$(window).on("resize", function() {
				if (!isMobileLayout()) {
					closeSidebar();
				}
			});

			$(document).on("click", ".bd-sidebar a", function() {
				if (isMobileLayout()) {
					closeSidebar();
				}
			});
		});

	</script>

</head>

<body class="flag">
	@include('static-layout/popup-alert')
	<div class="app-shell">
		<div class="app-grid">
			<header class="header">
				<div class="topbar">
					<div class="topbar-left">
						<button type="button" class="navbar-togglee" aria-label="Menu" aria-controls="userSidebar" aria-expanded="false">
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<div class="topbar-title">{{ $siteTitle ?? 'Personnel Logistic Accounting System' }}</div>
					</div>

					<div class="topbar-right">
						<button type="button" class="btn btn-default topbar-action" aria-label="Search">
							<i class="fa fa-search" aria-hidden="true"></i>
						</button>
						<button type="button" class="btn btn-default topbar-action" aria-label="Notifications">
							<i class="fa fa-bell-o" aria-hidden="true"></i>
						</button>
					</div>
				</div>
			</header>
	<div class="sidebar-backdrop"></div>
