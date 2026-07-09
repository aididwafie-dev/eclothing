@include('static-layout/header')
@include('static-layout/admin_sidebar')

                    </br>
                    <div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
                    <hr>

                    <div class="containerMain">
                        <div class="content table_center">
							<a class="back-btn" href="{{ url('/orders-uniform-wise') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
							<br/>
                           <i class="fa fa-line-chart" aria-hidden="true"></i> <B>Report of Uniform Type: {{$uniforms->uniform_type}} &amp; Unit type: {{$units->value}}</B>
                           <br/><br/>
                            @if(count($ordered_clothes) == 0)

                                <B>This Uniform is not ordered by anyone.</B>

                            @else
                              <a href="{{ url('/creat-excel-uniform/'.$uniforms->id) }}">
                                    <button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
                                </a><br /><br />
                               <table class="table" style="">
                                    <tr>
                                        <th>Clothes Type</th>
                                        <th>Clothes Size</th>
                                        <th>Quantity</th>
                                    </tr>
                                    <?php $number_fo_cloth = 0; ?>
                                    @foreach($ordered_clothes as $clothes)
                                        <?php $number_fo_cloth++; ?>
                                            <tr>
                                                <td>{{ $clothes->clothes_type }}</td>
                                                <td>{{ $clothes->clothes_size }}</td>
                                            </tr>
                                    @endforeach
                                </table>
                                <hr/>
                                <B>Total clothes for <I>Uniform {{$uniforms->uniform_type}} &amp; Unit type {{$units->value}}</I> => {{ $number_fo_cloth }}</B>
                                <br/>
                                <B>Total order of <I>Uniform {{$uniforms->uniform_type}} &amp; Unit type {{$units->value}}</I> => {{ $total_count }}</B>
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