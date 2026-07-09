@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> Order Report</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<a class="back-btn" href="{{ url('/admin/orders-report') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
							<br/>
							<i class="fa fa-filter" aria-hidden="true"></i> <B>Generate Report Cloth wise: </B>
							<hr>
							<form autocomplete="off" method="post" action="{{ url('/cloth-report') }}" name="order-report" id="order-report">
		    					<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
		    					<div class="form-group">
			    					<label class="label_">Select Uniform Type</label>
									<select required class="form-control" id="uniforms_id" name="uniforms_id">
					                    <option value="">Choose uniform type....</option>
					                        @foreach($uniforms as $uniform)
					                            <option value="{{$uniform->id}}">{{$uniform->uniform_type}} {{$uniform->uniform_name ? ' (' . $uniform->uniform_name . ')' : ''}}</option>
					                        @endforeach
					                </select>
					            </div>
				                
				                <div id="clothLoader" style="color: #007acc">
									<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
									<span style="font-size: 125%">Loading clothes....</span>
								</div>

								<div id="cloth"></div>
								
			                	<div class="subBtn">
							        <input class="btn btn-default" type="submit" value="GO" id="submit" name="submit" onclick="return validate()" /> 
							    </div>
							</form>
						</div>
					</div>
<!--#### 3 div open in sidebar ####-->
				</div>	
			</div>
		</div>
<!--#### 3 div open in sidebar ####-->
		<script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
        <script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.9/jquery.validate.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function(){
            	$("#clothLoader").hide();
                $("#uniforms_id").change(function(){
                	$("#clothLoader").show();
                	$('#submit').prop('disabled', true);
                    var value = $("#uniforms_id").val();
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        }
                    });
                    $.ajax({
                        type:'post',
                        url:'/load-cloth-ajax',
                        data:{uniforms_id:value},
                        success:function(data) {
                        	$("#clothLoader").hide();
                            $("#cloth").html(data);
                            $('#submit').prop('disabled', false);
                        }
                    });
                });
        	});
        </script>
    </body>
</html>