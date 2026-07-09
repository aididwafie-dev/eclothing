@include('static-layout/header')
@include('static-layout/admin_sidebar')

                    </br>
                    <div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
                    <hr>
                    <div class="containerMain">
                        <div class="content table_center">
                            <a class="back-btn" href="{{ url('/orders-cloth-wise') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
							<br/>
                            @if($ordered_clothes == 0)

                                    <B>Any Cloth of Uniform type {{$uniforms->uniform_type}} is not ordered.</B>

                            @else
                                <i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report of {{$ordered_clothes[0]['orders']->clothes}} of Uniform type {{$uniforms->uniform_type}}  {{$uniforms->uniform_name ? ' (' . $uniforms->uniform_name . ')' : ''}}</B>
                                <hr>
                                <a href="{{ url('/creat-excel-cloth/'.$uniforms->id.'/'.$ordered_clothes[0]['orders']->clothes_slug) }}">
                                    <button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
                                </a><br /><br />
                                <table class="table" style="">
                                    <tr>
                                        <th>Clothes Size</th>
                                        <th>Quantity</th>
                                    </tr>
                                    @foreach($ordered_clothes as $clothes)
										<tr>
											<td>{{ $clothes['orders']->size }}</td>
											<td>{{ $clothes['count'] }}</td>
										</tr>
                                    @endforeach
                                </table>
								<?php
									$total_count = 0;
									foreach($ordered_clothes as $clothes) {
										$total_count = $total_count + $clothes['count'];
									}
								?>
                                <hr/>
								<B>Total order of <I>{{$ordered_clothes[0]['orders']->clothes}}</I> of <I>Uniform {{$uniforms->uniform_type}}</I> => {{ $total_count }}</B>
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