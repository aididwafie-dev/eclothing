<html>
<head>
	<title>{{ $siteTitle ?? 'PLAS' }}</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no">

	{{-- Installable web app (and the Android TWA wrapper). Root-relative on
	     purpose: a manifest pulled from another origin cannot be installed,
	     and ASSET_URL may point elsewhere. --}}
	<link rel="manifest" href="/manifest.webmanifest">
	<meta name="theme-color" content="#0b1f3a">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="PLAS">
	<link rel="apple-touch-icon" href="/front_end/icons/apple-touch-icon.png">

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
		// Registered after load so it never competes with the page's own
		// requests. The worker caches static assets only -- see public/sw.js.
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', function () {
				navigator.serviceWorker.register('/sw.js').catch(function () {
					// An unregistered worker only costs offline support.
				});
			});
		}
	</script>
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

			// Wide data tables scroll sideways on small screens. Flag only the
			// containers that genuinely overflow, so the "scroll for more"
			// hint never appears on a table that already fits.
			function markScrollableTables() {
				$(".content.full, .table-responsive").each(function() {
					var el = this;
					var overflowing = el.scrollWidth > el.clientWidth + 1;
					$(el).toggleClass("is-scrollable", overflowing);
					if (overflowing && !$(el).children(".scroll-hint").length) {
						$(el).append(
							'<div class="scroll-hint"><i class="fa fa-arrows-h" aria-hidden="true"></i> Scroll sideways for more columns</div>'
						);
					}
				});
			}
			markScrollableTables();
			$(window).on("resize", markScrollableTables);
			// DataTables redraws rows via AJAX after load.
			$(document).on("draw.dt init.dt", markScrollableTables);
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
