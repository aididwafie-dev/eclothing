@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<div class="report-grid">
								<a href="#" class="report-card" data-toggle="modal" data-target="#reportUserUniformModal">
									<div class="report-icon report-icon-1"><i class="fa fa-users" aria-hidden="true"></i></div>
									<div class="report-card-title">Order Report With User Details For Each Uniform</div>
									<div class="report-card-subtitle">Uniform → Users → Sizes</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#reportUserUnitModal">
									<div class="report-icon report-icon-2"><i class="fa fa-building" aria-hidden="true"></i></div>
									<div class="report-card-title">Order Report With User Details For Each Unit</div>
									<div class="report-card-subtitle">Unit → Users → Orders</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#reportUniformModal">
									<div class="report-icon report-icon-3"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i></div>
									<div class="report-card-title">Order Report For Each Uniform</div>
									<div class="report-card-subtitle">Uniform → Size summary</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#reportUnitModal">
									<div class="report-icon report-icon-4"><i class="fa fa-sitemap" aria-hidden="true"></i></div>
									<div class="report-card-title">Order Report For Each Unit</div>
									<div class="report-card-subtitle">Unit → Uniform totals</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#reportUniformUnitModal">
									<div class="report-icon report-icon-5"><i class="fa fa-exchange" aria-hidden="true"></i></div>
									<div class="report-card-title">Order Report For Each Uniform and Unit</div>
									<div class="report-card-subtitle">Uniform + Unit breakdown</div>
								</a>

								<a href="#" class="report-card" data-toggle="modal" data-target="#reportClothModal">
									<div class="report-icon report-icon-6"><i class="fa fa-tags" aria-hidden="true"></i></div>
									<div class="report-card-title">Order Report For Each Cloth</div>
									<div class="report-card-subtitle">Cloth tag → Size summary</div>
								</a>
							</div>
						</div>
					</div>

					<div class="modal fade" id="reportUserUniformModal" tabindex="-1" role="dialog" aria-labelledby="reportUserUniformModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="reportUserUniformModalLabel">Order Report With User Details For Each Uniform</h4>
								</div>
								<div class="modal-body">
									Select a uniform and generate the report with user details and ordered sizes.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/orders-user-wise') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="reportUserUnitModal" tabindex="-1" role="dialog" aria-labelledby="reportUserUnitModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="reportUserUnitModalLabel">Order Report With User Details For Each Unit</h4>
								</div>
								<div class="modal-body">
									Select a unit and view orders by users within that unit.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/orders-user-wise-unit') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="reportUniformModal" tabindex="-1" role="dialog" aria-labelledby="reportUniformModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="reportUniformModalLabel">Order Report For Each Uniform</h4>
								</div>
								<div class="modal-body">
									Select a uniform and generate a size summary report.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/orders-uniform-wise') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="reportUnitModal" tabindex="-1" role="dialog" aria-labelledby="reportUnitModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="reportUnitModalLabel">Order Report For Each Unit</h4>
								</div>
								<div class="modal-body">
									Select a unit and view totals grouped by uniform type.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/orders-unit-wise') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="reportUniformUnitModal" tabindex="-1" role="dialog" aria-labelledby="reportUniformUnitModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="reportUniformUnitModalLabel">Order Report For Each Uniform and Unit</h4>
								</div>
								<div class="modal-body">
									Select a uniform and a unit to generate a combined breakdown report.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/orders-uniform-unit-wise') }}" class="btn btn-brand">Open Report</a>
									<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
								</div>
							</div>
						</div>
					</div>

					<div class="modal fade" id="reportClothModal" tabindex="-1" role="dialog" aria-labelledby="reportClothModalLabel">
						<div class="modal-dialog" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title" id="reportClothModalLabel">Order Report For Each Cloth</h4>
								</div>
								<div class="modal-body">
									Select a uniform and cloth tag to generate a size summary for that cloth.
								</div>
								<div class="modal-footer">
									<a href="{{ url('/orders-cloth-wise') }}" class="btn btn-brand">Open Report</a>
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
