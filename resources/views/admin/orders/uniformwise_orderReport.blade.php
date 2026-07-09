@include('static-layout/header')
@include('static-layout/admin_sidebar')

</br>
<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
<hr>

<div class="containerMain">
    <div class="content table_center">
        <a class="back-btn" href="{{ url('/orders-uniform-wise') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
        <br/>
        <i class="fa fa-line-chart" aria-hidden="true"></i> 
        <b>Report of Uniform Type: {{$uniforms->uniform_type}} {{$uniforms->uniform_name ? ' (' . $uniforms->uniform_name . ')' : ''}}</b>
        <br/><br/>
        @if($ordered_clothes == 0)
            <b>This Uniform is not ordered by anyone.</b>
        @else
            <table class="table">
                <tr>
                    <th>Clothes Type</th>
                    <th>Clothes Size</th>
                    <th>Quantity</th>
                </tr>
                <?php $number_of_cloth = 0; ?>
                @foreach($ordered_clothes as $orderedClothes)
                    <?php $number_of_cloth++; ?>
                    @foreach($orderedClothes as $clothes)
                        <tr>
                            <td>{{ $clothes['orders']->clothes }}</td>
                            <td>{{ $clothes['orders']->size }}</td>
                            <td>{{ $clothes['count'] }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </table>
            <?php
                $total_count = 0;
                foreach($ordered_clothes[0] as $clothes) {
                    $total_count += $clothes['count'];
                }
            ?>
            <hr/>
            <b>Total clothes for <i>Uniform {{$uniforms->uniform_type}}</i> {{$uniforms->uniform_name ? ' (' . $uniforms->uniform_name . ')' : ''}} => {{ $number_of_cloth }}</b>
            <br/>
            <b>Total order of <i>Uniform {{$uniforms->uniform_type}}</i> {{$uniforms->uniform_name ? ' (' . $uniforms->uniform_name . ')' : ''}} => {{ $total_count }}</b>
            <br/><br/>
            <a href="{{ url('/creat-excel-uniform/'.$uniforms->id) }}">
                <button type="button" class="btn"><i class="fa fa-download" aria-hidden="true"></i> Download Excel File</button>
            </a>
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
