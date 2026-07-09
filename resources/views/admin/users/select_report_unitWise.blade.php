@include('static-layout/header')
@include('static-layout/admin_sidebar')

					</br>
					<div class="title"><i class="fa fa-file-excel-o" aria-hidden="true"></i> User Reports</div>
					<hr>
					<div class="containerMain">
						<div class="content">
							<a class="back-btn" href="{{ url('/admin/users-report') }}"><i class="fa fa-arrow-left" aria-hidden="true"></i> Back</a>
							<br/>
							<i class="fa fa-filter" aria-hidden="true"></i> <B>Generate Report of Users in Unit: </B>
							<hr>
							<form autocomplete="off" method="post" action="{{ url('/user-report-with-unit') }}" name="order-report" id="order-report">
		    					<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
		    					<div class="form-group">
			    					<label class="label_">Select Unit Type</label>
									<select required class="form-control" id="unit_id" name="unit_id">
					                    <option value="">Choose unit</option>
					                         @foreach($units as $unit)
					                            <option value="{{$unit->id}}">{{$unit->value}}</option>
					                        @endforeach
					                </select>
					            </div>
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
    </body>
</html>