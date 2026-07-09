@include('static-layout/header')
@include('static-layout/admin_sidebar')

                    </br>
                    <div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
                    <hr>
					
						<div class="containerMain other">
							<div class="content full clearfix">
								<a class="back-btn" href="{{ url('/orders-user-wise') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
								<br/>
								<i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report With User Details of Uniform Type: {{$uniforms->uniform_type}} {{$uniforms->uniform_name ? ' (' . $uniforms->uniform_name . ')' : ''}}</B>
								<br/><br/>
								<?php if($orders_detail != 0) { ?>
									<a href="{{ url('/excel-uniform-user-details/'.$uniforms->id) }}">
									<button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
								</a><br /><br />
								<div class="table-responsive">
									<table class="table table-bordered">
										<thead>
											<tr>
												<th>#</th>
												<th>SERVICE ID</th>
												<th>RANK</th>
												<th>NAME</th>
												<th>UNIT</th>
												@foreach($uniform_clothes as $uniform_cloth)
													<th>{{ $uniform_cloth->clothes_type }}</th>
												@endforeach
											</tr>
										</thead>
										<?php
										$results = [];
										foreach($orders_detail as $id => $order_details) {
											$results[$id] = [
												intval(str_pad($order_details['user_details']->s_id, 8, "0", STR_PAD_RIGHT)),
												$order_details['user_details']->s_id, 
												(isset($order_details['rank']->value) ? $order_details['rank']->value : ''),
												$order_details['user_details']->name, 
												(isset($order_details['unit']) && $order_details['unit']->value ? (is_array($order_details['unit']->value) ? implode(",",$order_details['unit']->value) : $order_details['unit']->value) : '-')
											];
											
											foreach($order_details['cloth_details'] as $cloth_detail) {
												$results[$id][] = $cloth_detail->size;
											}
										}
	
	array_multisort(array_column($results, 0),  SORT_ASC,
                array_column($results, 2), SORT_ASC,
                $results);
	
										?>
										@foreach($results as $id => $result)
										<?php array_shift($result); ?>
											<tbody>
												<tr>
													<td>{{ $id+1 }}</td>
													@foreach($result as $res)
														<td>{{ $res }}</td>
													@endforeach
												</tr>
											</tbody>
										@endforeach
									</table>
								</div>
							
								<?php } else { ?>
									<B>This Uniform is not ordered by anyone.</B>
								<?php } ?>
							</div>
						</div>
					
<!--#### 3 div open in sidebar ####-->
                </div>  
            </div>
        </div>
<!--#### 3 div open in sidebar ####-->
    </body>
</html>