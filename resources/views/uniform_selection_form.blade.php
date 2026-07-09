<br />
<?php
	$uniformDisplayName = $uniform->uniform_name ? $uniform->uniform_name : $uniform->uniform_type;
	$cartItems = isset($cartItems) && is_array($cartItems) ? $cartItems : [];
	$cartCount = isset($cartCount) ? (int) $cartCount : 0;
?>

<div class="shop-layout">
	<div class="shop-items">
		<div class="shop-header">
			<div>
				<div class="shop-title">{{ $uniformDisplayName }}</div>
				<div class="shop-subtitle">Choose cloth items and add them to cart</div>
			</div>
		</div>

		<div class="shop-grid">
			@foreach($uniform_clothes as $clothes)
				<?php
					$inputId = 'size_' . $clothes->clothes_slug;
					$inCart = isset($clothes->in_cart) && $clothes->in_cart;
					$cartValue = isset($clothes->cart_value) ? $clothes->cart_value : null;
				?>
				<div class="shop-card">
					<img class="shop-img" src="{{ asset('front_end/images/banner-bg.jpg') }}" alt="cloth" />
					<div class="shop-card-body">
						<div class="shop-item-name">{{ $uniformDisplayName }}</div>
						<div class="shop-item-meta">{{ $clothes->clothes_type }}</div>

						<div class="shop-controls">
							@if($clothes->clothes_size == '')
								<div class="form-check">
									<input class="form-check-input" type="checkbox" id="{{$inputId}}" {{ $inCart ? "checked" : "" }}>
									<label class="form-check-label" for="{{$inputId}}">Add this item</label>
								</div>
							@elseif($clothes->clothes_size == 'FIX')
								<label class="label_">Size</label>
								<input class="form-control" type="text" id="{{$inputId}}" value="{{$clothes->clothes_size}}" readonly />
							@else
								<?php $size_check = str_replace('-','',str_replace(' ','',$clothes->clothes_size)); ?>
								@if(is_numeric($size_check))
									<label class="label_">Size</label>
									<input class="form-control" type="text" id="{{$inputId}}" placeholder="{{$clothes->clothes_size}}" value="{{ is_string($cartValue) ? $cartValue : (isset($clothes->ordered_size) ? $clothes->ordered_size : '') }}" />
								@else
									<?php
										$size_range = explode('-', str_replace(' ','',$clothes->clothes_size));
										if(!isset($size_range[1])){
											$size_range = explode(',', str_replace(' ','',$clothes->clothes_size));
										}
									?>
									@if(isset($size_range[1]))
										<?php
											$size_array = [];
											foreach ($sizes as $size) {
												$size_array[] = $size->value;
											}
											$start = array_search($size_range[0],$size_array);
											$end = array_search($size_range[1],$size_array);
										?>
										<label class="label_">Size</label>
										<select class="form-control" id="{{$inputId}}" {{ strtolower($clothes->clothes_type) == 'accessories' ? 'multiple' : '' }}>
											<option value="">Choose your size....</option>
											@foreach($size_array as $key => $size)
												@if($key >= $start)
													<option value="{{$size}}" {{ (is_array($cartValue) && in_array($size, $cartValue)) || (is_string($cartValue) && $cartValue == $size) ? "selected" : (isset($clothes->ordered_size) && $clothes->ordered_size == $size ? "selected" : "") }}>{{$size}}</option>
													@if($key == $end)
														<?php break; ?>
													@endif
												@endif
											@endforeach
										</select>
									@else
										<label class="label_">Options</label>
										<?php $options = explode("|", $clothes->clothes_size); ?>
										<select class="form-control" id="{{$inputId}}" {{ strtolower($clothes->clothes_type) == 'accessories' ? 'multiple' : '' }}>
											<option value="">Choose....</option>
											@foreach($options as $option)
												<?php $opt = trim($option); ?>
												<option value="{{ $opt }}" {{ (is_array($cartValue) && in_array($opt, $cartValue)) || (is_string($cartValue) && $cartValue == $opt) ? "selected" : (isset($clothes->ordered_size) && $clothes->ordered_size == $opt ? "selected" : "") }}>{{ $opt }}</option>
											@endforeach
										</select>
									@endif
								@endif
							@endif
						</div>

						<div class="shop-actions">
							<a href="#" class="btn btn-brand cart-add" data-uniform-id="{{ $clothes->uniforms_id }}" data-clothes-slug="{{ $clothes->clothes_slug }}" data-input="#{{$inputId}}">
								{{ $inCart ? 'Update Cart' : 'Add to Cart' }}
							</a>
							@if($inCart)
								<a href="#" class="btn btn-default cart-remove" data-uniform-id="{{ $clothes->uniforms_id }}" data-clothes-slug="{{ $clothes->clothes_slug }}">Remove</a>
							@endif
						</div>
					</div>
				</div>
			@endforeach
		</div>
	</div>

	<div class="shop-cart">
		<div class="shop-cart-card">
			<div class="shop-cart-title">Cart</div>
			<div class="shop-cart-subtitle">{{ $cartCount }} items in cart</div>
			<hr>
			@if(count($cartItems))
				@foreach($cartItems as $item)
					<div class="shop-cart-row">
						<div class="shop-cart-row-main">
							<div class="shop-cart-name">{{ $item['uniform_name'] }}</div>
							<div class="shop-cart-meta">{{ $item['clothes_type'] }} ·
								@if(is_array($item['size']))
									{{ implode(', ', $item['size']) }}
								@else
									{{ $item['size'] }}
								@endif
							</div>
						</div>
						<a href="#" class="shop-cart-remove cart-remove" data-uniform-id="{{ $item['uniforms_id'] }}" data-clothes-slug="{{ $item['clothes_slug'] }}" aria-label="Remove">
							<i class="fa fa-times" aria-hidden="true"></i>
						</a>
					</div>
				@endforeach
				<div class="shop-cart-actions">
					<a href="#" class="btn btn-brand cart-checkout">Checkout</a>
				</div>
			@else
				<div class="shop-empty">No items yet. Add cloth items to your cart.</div>
			@endif
		</div>
	</div>
</div>
