@include('static-layout/header')
@include('static-layout/admin_sidebar')

                    </br>
                    <div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
                    <hr>
                    <div class="containerMain">
                        <div class="content table_center">
                            <a class="back-btn" href="{{ url('/orders-unit-wise') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
							<br/>
                            @if(count($orders) == 0)

                                    <B>No uniforms ordered.</B>

                            @else
                                <i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report of Uniform orders for unit {{$unit->value}}</B>
                                <hr>
                                <table class="table" style="">
                                    <tr>
                                        <th>Uniform type</th>
                                        <th>Quantity</th>
                                    </tr>
                                    <?php $uniform_counts = [];
																										$total_count = 0;

                                    foreach($orders as $order) {
																			if (!isset($uniform_counts[$order->uniforms_id])) {
																				$uniform_counts[$order->uniforms_id] = 0;
																			}
                                    $uniform_counts[$order->uniforms_id]++;
																												$total_count++;
                                    }
																	
																	foreach ($uniform_counts as $uniform_id => $count) { ?>
										<tr>
											<td>{{ (isset($uniforms[$uniform_id]) ? $uniforms[$uniform_id] : '-') }}</td>
											<td>{{ $count }}</td>
										</tr>
                               <?php } ?>
                                </table>
                                <hr/>
								<B>Total orders for unit {{$unit->value}} => {{ $total_count }}</B>
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