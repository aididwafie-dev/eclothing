@include('static-layout/header')
@include('static-layout/admin_sidebar')

<?php
// echo "<pre>";
// print_r($userOrder);
// echo "<pre>";die;
?>
			<br>
			<div class="title"><i class="fa fa-pencil-square" aria-hidden="true"></i> Edit Uniform size of User</div>
			<hr>
			<div class="containerMain">
				<div class="content">
					<i class="fa fa-pencil" aria-hidden="true"></i> <B>Uniform: {{ $uniforms->uniform_type }}</B>
					<hr>
					<form autocomplete="off" method="post" action="{{ url('/uniform-details-saveEdit') }}" name="uniform-details" id="uniform-details">
						<input type = "hidden" name = "_token" value = "<?php echo csrf_token(); ?>">
						<input type = "hidden" name="order_id" value = "{{ $userOrder->id }}">
						<input type = "hidden" name="user_id" value = "{{ $userOrder->user_id }}">
						@foreach($ordered_clothes as $orderedClothes)
							<?php
								$clothes_name = implode(' ', explode('_', $orderedClothes->clothes));
							?>
							<label class="label_">{{$clothes_name}}</label>
							@if($orderedClothes->size == 'FIX')
								<input class="form-control" type = "text" id="{{$clothes_name}}" name="{{ $orderedClothes->clothes_slug }}" value="{{ $orderedClothes->size }}" readonly/>
							@else
								<input class="form-control size_order" type = "text" id="{{$clothes_name}}" name="{{ $orderedClothes->clothes_slug }}" value="{{ $orderedClothes->size }}" required/>
							@endif
						@endforeach
						<div class="subBtn">
					        <input class="btn btn-default" type="submit" value="SAVE" id="submit" name="submit" /> 
					        <a class="btn btn-default" href="{{ url('/admin-cancel') }}">CANCEL</a>
					    </div>
					</form>
				</div>
			</div>
<!--#### 3 div open in sidebar ####-->
		<script type="text/javascript">
			$('.size_order').keyup(function(){
				var size_order = $(this).val().toUpperCase();
				$(this).val(size_order);
			});
		</script>
		</div>	
	</div>
</div>
<!--#### 3 div open in sidebar ####-->