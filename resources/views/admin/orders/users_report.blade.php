@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> User Reports</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="report-grid">
								<a href="#" class="report-card" data-toggle="modal" data-target="#userReportByUnitModal">
									<div class="report-icon report-icon-2"><i class="fa fa-sitemap" aria-hidden="true"></i></div>
									<div class="report-card-title">Users Report By Units</div>
									<div class="report-card-subtitle">List users grouped by unit</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#userReportUniformTagsModal">
									<div class="report-icon report-icon-6"><i class="fa fa-tags" aria-hidden="true"></i></div>
									<div class="report-card-title">Users Report of Uniform Tags</div>
									<div class="report-card-subtitle">Uniform tag usage & counts</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#userReportWithoutOrdersModal">
									<div class="report-icon report-icon-4"><i class="fa fa-user-times" aria-hidden="true"></i></div>
									<div class="report-card-title">Users Report without Orders</div>
									<div class="report-card-subtitle">Users with no order record</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#userReportStrengthUnitsModal">
									<div class="report-icon report-icon-1"><i class="fa fa-bar-chart" aria-hidden="true"></i></div>
									<div class="report-card-title">Users Report for Strength by Units</div>
									<div class="report-card-subtitle">Strength summary by unit</div>
								</a>
							</div>
						</div>
					</div>

					<div class="modal fade" id="userReportByUnitModal" tabindex="-1" role="dialog" aria-labelledby="userReportByUnitModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="userReportByUnitModalLabel">Users Report By Units</h4>
								</div>
								<div class="modal-body">
									View users grouped by their unit.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/user-report-with-unit') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="userReportUniformTagsModal" tabindex="-1" role="dialog" aria-labelledby="userReportUniformTagsModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="userReportUniformTagsModalLabel">Users Report of Uniform Tags</h4>
								</div>
								<div class="modal-body">
									View report by uniform tags (uniform name / tag usage).
								</div>
								<div class="modal-footer">
									<a href="{{ url('/user-report-with-uniform-name') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="userReportWithoutOrdersModal" tabindex="-1" role="dialog" aria-labelledby="userReportWithoutOrdersModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="userReportWithoutOrdersModalLabel">Users Report without Orders</h4>
								</div>
								<div class="modal-body">
									View users who do not have any orders.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/user-report-without-orders') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="userReportStrengthUnitsModal" tabindex="-1" role="dialog" aria-labelledby="userReportStrengthUnitsModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="userReportStrengthUnitsModalLabel">Users Report for Strength by Units</h4>
								</div>
								<div class="modal-body">
									View user strength summary grouped by unit.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/user-report-strength-units') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>
<!--#### 3 div open in sidebar ####-->
				</div>	
			</div>
		</div>
<!--#### 3 div open in sidebar ####-->
    </body>
</html>
