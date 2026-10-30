<html>
<head>
	<title>{{ $siteTitle ?? 'e-Clothing' }}</title>
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
	<meta name="apple-mobile-web-app-title" content="e-Clothing">
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
						<div class="topbar-title">{{ $siteTitle ?? 'e-Clothing' }}</div>
					</div>

					<div class="topbar-right">
						@include('static-layout/language-toggle')
						<button type="button" class="btn btn-default topbar-action" aria-label="Search">
							<i class="fa fa-search" aria-hidden="true"></i>
						</button>
						@php
							// Both a member and an admin session can be open in this
							// browser at once (see UserController::userLogout /
							// AdminController::adminLogout, which only forget their
							// own keys). When both are present, which inbox the bell
							// shows follows which area the current page belongs to.
							$hasAdminSession = (bool) session('admin_id');
							$hasUserSession = (bool) session('user_id');
							$currentRouteAction = (string) \Illuminate\Support\Facades\Route::currentRouteAction();
							$looksLikeAdminPage = str_contains($currentRouteAction, 'App\Http\Controllers\Admin')
								|| str_contains($currentRouteAction, 'AnnouncementsController');
							$notifContext = ($hasAdminSession && (!$hasUserSession || $looksLikeAdminPage))
								? 'admin'
								: ($hasUserSession ? 'user' : null);
						@endphp
						@if($notifContext)
						<div class="notif-wrap" id="notifWrap"
							data-index-url="{{ $notifContext === 'admin' ? route('admin.notifications') : route('user.notifications') }}"
							data-read-url="{{ $notifContext === 'admin' ? route('admin.notifications.read') : route('user.notifications.read') }}">
							<button type="button" class="btn btn-default topbar-action" id="notifBell" aria-label="Notifications" aria-haspopup="true" aria-expanded="false">
								<i class="fa fa-bell-o" aria-hidden="true"></i>
								<span class="notif-badge" id="notifBadge" style="display:none;">0</span>
							</button>
							{{-- Moved to <body> by the script below and fixed-positioned under the bell. --}}
							<div class="notif-panel" id="notifDropdown" role="dialog" aria-label="Pemberitahuan" style="display:none;"
								data-order-url="{{ $notifContext === 'admin' ? url('/admin/uniform-orders/__ID__') : route('user.ordered-uniform') }}">
								<div class="notif-panel-header">
									<h2 class="notif-panel-title">Pemberitahuan</h2>
									<button type="button" class="notif-panel-close" id="notifClose" aria-label="Tutup">
										<i class="fa fa-times" aria-hidden="true"></i>
									</button>
								</div>
								<div class="notif-panel-list" id="notifList">
									<div class="notif-panel-empty">Memuatkan...</div>
								</div>
							</div>
						</div>
						@endif
					</div>
				</div>
			</header>
	<div class="sidebar-backdrop"></div>

	@if($notifContext)
	<script type="text/javascript">
		(function () {
			var $wrap = $('#notifWrap');
			var indexUrl = $wrap.data('index-url');
			var readUrl = $wrap.data('read-url');
			var $bell = $('#notifBell');
			var $badge = $('#notifBadge');
			var $dropdown = $('#notifDropdown');
			var $list = $('#notifList');
			var orderUrl = String($dropdown.data('order-url') || '');
			var csrf = $('meta[name="_token"]').attr('content');
			var open = false;

			// Fixed to the viewport from <body> so no header, grid or
			// overflow rule can clip or cover it.
			$dropdown.appendTo('body');

			// Icon and colours per type from OrderNotificationService /
			// AdminNotificationService -- same table as the Next.js AppHeader.
			var green = ['#1a7f45', 'rgba(26, 127, 69, 0.12)'];
			var red = ['#c0392b', 'rgba(192, 57, 43, 0.12)'];
			var blue = ['#0f5bb5', 'rgba(51, 145, 255, 0.12)'];
			var yellow = ['#6b5200', 'rgba(249, 209, 22, 0.2)'];
			var typeIcons = {
				order_approved: ['fa-check-circle', green],
				admin_order_approved: ['fa-check-circle', green],
				order_completed: ['fa-check', green],
				order_rejected: ['fa-times-circle', red],
				order_collection_timeout: ['fa-clock-o', red],
				order_processing: ['fa-cog', blue],
				order_collection_date_updated: ['fa-calendar', blue],
				order_remarks_updated: ['fa-comment-o', blue],
				admin_new_order: ['fa-shopping-cart', yellow],
				admin_order_edited: ['fa-pencil', yellow],
				announcement: ['fa-bullhorn', yellow]
			};
			var defaultIcon = ['fa-bell-o', ['#0b1f3a', 'rgba(0, 26, 56, 0.06)']];

			function escapeHtml(value) {
				return $('<div>').text(value == null ? '' : value).html();
			}

			// "Y-m-d H:i:s" (server local time) -> "5 minit", "3 jam", "1 hari".
			function timeAgo(value) {
				var then = new Date(String(value).replace(' ', 'T'));
				if (isNaN(then.getTime())) return String(value);
				var minutes = Math.floor((Date.now() - then.getTime()) / 60000);
				if (minutes < 1) return 'Baru sahaja';
				if (minutes < 60) return minutes + ' minit';
				var hours = Math.floor(minutes / 60);
				if (hours < 24) return hours + ' jam';
				var days = Math.floor(hours / 24);
				if (days < 30) return days + ' hari';
				return then.toLocaleDateString('ms-MY', { day: 'numeric', month: 'short', year: 'numeric' });
			}

			// Just below the bell, right-aligned to it, kept 16px inside the viewport.
			function position() {
				var rect = $bell[0].getBoundingClientRect();
				var panelWidth = Math.min(520, window.innerWidth - 32);
				var right = Math.min(Math.max(16, window.innerWidth - rect.right), window.innerWidth - panelWidth - 16);
				$dropdown.css({ top: rect.bottom + 12, right: right });
			}

			function setOpen(next) {
				open = next;
				if (open) position();
				$dropdown.toggle(open);
				$bell.attr('aria-expanded', open ? 'true' : 'false');
			}

			function renderBadge(count) {
				if (count > 0) {
					$badge.text(count > 99 ? '99+' : count).show();
				} else {
					$badge.hide();
				}
			}

			function renderList(notifications) {
				if (!notifications.length) {
					$list.html('<div class="notif-panel-empty">Tiada pemberitahuan.</div>');
					return;
				}
				var html = notifications.map(function (n) {
					var icon = typeIcons[n.type] || defaultIcon;
					var href = n.order_id != null ? orderUrl.replace('__ID__', n.order_id) : '';
					var tag = href ? 'a' : 'div';
					return '<' + tag + ' class="notif-panel-item' + (n.read ? '' : ' is-unread') + '"'
						+ (href ? ' href="' + escapeHtml(href) + '"' : '') + '>'
						+ '<span class="notif-panel-icon" style="color:' + icon[1][0] + ';background:' + icon[1][1] + '">'
						+ '<i class="fa ' + icon[0] + '" aria-hidden="true"></i></span>'
						+ '<span class="notif-panel-text">'
						+ '<span class="notif-panel-item-title" style="display:block">' + escapeHtml(n.title) + '</span>'
						+ '<span class="notif-panel-item-body">' + escapeHtml(n.body) + '</span>'
						+ '</span>'
						+ '<span class="notif-panel-time" title="' + escapeHtml(n.created_at) + '">' + escapeHtml(timeAgo(n.created_at)) + '</span>'
						+ '</' + tag + '>';
				}).join('');
				$list.html(html);
			}

			function fetchNotifications(markAsRead) {
				$.ajax({
					type: 'get',
					url: indexUrl,
					dataType: 'json'
				}).done(function (data) {
					renderBadge(data.unread_count || 0);
					if (markAsRead) {
						renderList(data.notifications || []);
						if ((data.unread_count || 0) > 0) {
							$.ajax({
								type: 'post',
								url: readUrl,
								headers: { 'X-CSRF-TOKEN': csrf },
								dataType: 'json'
							}).done(function (readData) {
								renderBadge(readData.unread_count || 0);
							});
						}
					}
				}).fail(function () {
					if (markAsRead) {
						$list.html('<div class="notif-panel-empty">Gagal memuatkan pemberitahuan.</div>');
					}
				});
			}

			$bell.on('click', function (e) {
				e.stopPropagation();
				setOpen(!open);
				if (open) {
					$list.html('<div class="notif-panel-empty">Memuatkan...</div>');
					fetchNotifications(true);
				}
			});

			$('#notifClose').on('click', function () {
				setOpen(false);
			});

			$(document).on('click', function (e) {
				if (open && !$.contains($wrap[0], e.target) && !$.contains($dropdown[0], e.target)) {
					setOpen(false);
				}
			});

			$(document).on('keydown', function (e) {
				if (open && e.key === 'Escape') setOpen(false);
			});

			// Keep it pinned under the bell when the window resizes or the page scrolls.
			$(window).on('resize', function () {
				if (open) position();
			});
			window.addEventListener('scroll', function () {
				if (open) position();
			}, true);

			// Badge only, on load -- the full list (and marking read) waits
			// for the admin/member to actually open the dropdown.
			fetchNotifications(false);

			// Poll so a notification that lands while this page is sitting
			// open (an admin watching the queue, a member waiting on an
			// update) still shows up without a manual reload. Skips the
			// read-marking branch unless the dropdown is open, same as a
			// manual click would.
			setInterval(function () {
				fetchNotifications(open);
			}, 30000);
		})();
	</script>
	@endif
