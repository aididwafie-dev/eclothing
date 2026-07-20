<?php
	$currentRouteAction = Route::currentRouteAction();
	$current_class_method = explode('@', $currentRouteAction);
?>

		<div class="bd-sidebar pull-left">
			<nav class="collapse bd-links">

				<div class="sidebar-brand">
					<img src="{{ $siteLogoUrl ?? asset('front_end/images/logo.png') }}" class="sidebar-logo" alt="logo" />
					<div class="sidebar-brand-title">{{ $siteTitle ?? 'PLAS' }}</div>
				</div>

				<a href="#" class="btn btn-link btn-pnl btn-block sidebar-close"><i class="fa fa-times" aria-hidden="true"></i> Close</a>

				<a href="{{ url('/new-admin') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminNewListController' && ($current_class_method[1] == 'index' || $current_class_method[1] == 'getNewAdminDetails') ){ echo 'active'; } ?>"><i class="fa fa-user-plus" aria-hidden="true"></i> Add New Admin</button>
				</a>

				<a href="{{ url('/all-admins') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminNewListController' && $current_class_method[1] == 'getAllAdminsList'){ echo 'active'; } ?>"><i class="fa fa-user-circle-o" aria-hidden="true"></i> All Admins</button>
				</a>

				<a href="{{ url('/all-users') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminController' && in_array($current_class_method[1], ['allUsersTable', 'ajaxDatatableUsersDetails', 'fromEditUserBasicDetails', 'changeBasicDetails', 'fromEditUserPersonalDetails', 'ajaxLoadRankValuesForAdmin', 'changePersonalDetails', 'changeUserAccessStatus', 'changeUserAccessBlockAll', 'changeUserAccessUnblockAll', 'listUserUniformDetails', 'fromEditUserUniformDetails', 'saveUniformEditedDetails', 'deleteGeneralUser', 'deleteOrder'])){ echo 'active'; } ?>"><i class="fa fa-users" aria-hidden="true"></i> All Users</button>
				</a>

				<a href="{{ route('admin.uniform-orders') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminController' && in_array($current_class_method[1], ['uniformOrdersList', 'uniformOrderDetail', 'updateUniformOrderStatus'])){ echo 'active'; } ?>"><i class="fa fa-clipboard" aria-hidden="true"></i> Uniform Orders</button>
				</a>

				<a href="{{ url('/admin/uniform') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminUniformController'){ echo 'active'; } ?>"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> Uniforms</button>
				</a>
				
				<a href="{{ url('/admin/unit') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminUnitController'){ echo 'active'; } ?>"><i class="fa fa-arrows-h" aria-hidden="true"></i> Units</button>
				</a>

				<a href="{{ url('/admin/tred') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminTredController'){ echo 'active'; } ?>"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> Tred</button>
				</a>

				<a href="{{ url('/admin/size') }}" class="hide">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminSizeController'){ echo 'active'; } ?>"><i class="fa fa-arrows" aria-hidden="true"></i> Sizes</button>
				</a>

				<a href="{{ url('/admin/orders-report') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminReportController'){ echo 'active'; } ?>"><i class="fa fa-print" aria-hidden="true"></i> Order Reports</button>
				</a>

				<a href="{{ url('/admin/users-report') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminUsersReportController'){ echo 'active'; } ?>"><i class="fa fa-print" aria-hidden="true"></i> User Reports</button>
				</a>

				<a href="{{ url('/admin/announcements') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AnnouncementsController'){ echo 'active'; } ?>"><i class="fa fa-envelope-o" aria-hidden="true"></i> Announcements</button>
				</a>

				<a href="{{ url('/admin/change-password') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminPasswordController'){ echo 'active'; } ?>"><i class="fa fa-lock" aria-hidden="true"></i> Change Password</button>
				</a>

				<a href="{{ url('/admin/system-settings') }}">
					<button type="button" class="btn btn-pnl btn-block <?php if ($current_class_method[0] == 'App\Http\Controllers\AdminController' && in_array($current_class_method[1], ['systemSettings', 'saveSystemSettings'])){ echo 'active'; } ?>"><i class="fa fa-cog" aria-hidden="true"></i> Tetapan Sistem</button>
				</a>

				<a href="{{ url('/admin-logout') }}">
					<button type="button" class="btn btn-pnl btn-block"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</button>
				</a>
			</nav>				
		</div>

		<div class="rightPanel app-main">
