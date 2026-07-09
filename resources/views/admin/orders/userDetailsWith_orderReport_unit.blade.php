@include('static-layout/header')
@include('static-layout/admin_sidebar')

                    </br>
                    <div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
                    <hr>
					
					
						<div class="containerMain other">
							<div class="content full clearfix">
								<a class="back-btn" href="{{ url('/orders-user-wise-unit') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
								<br/>
								<i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report With User Details of Unit: {{$unit->value}}</B>
								<br/><br/>
								@if(count($users) != 0)
								<div class=" table-responsive">
									<table class="table table-bordered">
										<thead>
											<tr>
												<th>#</th>
												<th>SERVICE ID</th>
												<th>RANK</th>
												<th>NAME</th>
												@foreach($uniforms as $uniform_id => $uniform_name)
													<th>{{ $uniform_name }}</th>
												@endforeach
											</tr>
										</thead>
										<?php 
										
										$id = 1;
										?>
										@foreach($users as $user)
											<tbody>
												<tr>
													<td>{{ $id++ }}</td>
													<td>{{ $user->s_id }}</td>
													<td>{{ $pangkats[$user->pangkat] }}</td>
													<td>{{ $user->name }}</td>
													@foreach($uniforms as $uniform_id => $uniform_name)
													<td>
													@foreach($orders as $order)
														@if($uniform_id == $order->uniforms_id && $user->user_id == $order->user_id)
														✓
														@endif
													@endforeach
													</td>
													@endforeach
												</tr>
												 
											</tbody>
										@endforeach
									</table>
								</div>
								<br/>
								<a href="{{ url('/excel-uniform-user-details-unit/'.$unit->id) }}">
									<button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
								</a>
								@else
									<B>This Uniform is not ordered by anyone.</B>
								@endif
							</div>
						</div>
					
<!--#### 3 div open in sidebar ####-->
                </div>  
            </div>
        </div>
<!--#### 3 div open in sidebar ####-->
    </body>
</html>