<br />
<?php
	$uniformDisplayName = $uniform->uniform_name ? $uniform->uniform_name : $uniform->uniform_type;
	$cartItems = isset($cartItems) && is_array($cartItems) ? $cartItems : [];
	$cartCount = isset($cartCount) ? (int) $cartCount : 0;
	$currentUniformCartCount = count($cartItems);
?>

<div class="shop-cart-layout">
	<div class="shop-board">
		<div class="shop-board-header">
			<div>
				<div class="shop-board-title">{{ __('app.cart.title') }}</div>
				<div class="shop-board-subtitle">{{ __('app.cart.subtitle', ['uniform' => $uniformDisplayName, 'selected' => $currentUniformCartCount, 'total' => $cartCount]) }}</div>
			</div>
			<div class="shop-board-badge">
				<i class="fa fa-shirtsinbulk" aria-hidden="true"></i> {{ $uniformDisplayName }}
			</div>
		</div>

		<div class="shop-board-columns">
			<div>{{ __('app.cart.item') }}</div>
			<div>{{ __('app.cart.selection') }}</div>
			<div>{{ __('app.cart.status') }}</div>
			<div>{{ __('app.cart.action') }}</div>
		</div>

		<div class="shop-board-list">
			@foreach($uniform_clothes as $clothes)
				<?php
					$inputId = 'size_' . $clothes->clothes_slug;
					$inCart = isset($clothes->in_cart) && $clothes->in_cart;
					$cartValue = isset($clothes->cart_value) ? $clothes->cart_value : null;
					$clothPhotoPath = !empty($clothes->clothes_photo) ? (strpos($clothes->clothes_photo, '/') !== false ? $clothes->clothes_photo : 'uploads/' . $clothes->clothes_photo) : null;
					$uniformPhotoPath = !empty($uniform->uniform_photo) ? (strpos($uniform->uniform_photo, '/') !== false ? $uniform->uniform_photo : 'uploads/' . $uniform->uniform_photo) : null;
					$itemImage = !empty($clothes->clothes_photo)
						? asset($clothPhotoPath)
						: (!empty($uniform->uniform_photo)
							? asset($uniformPhotoPath)
							: asset('front_end/images/uniforms/' . $uniform->uniform_type . '.jpg'));
					$orderedSize = isset($clothes->ordered_size) ? $clothes->ordered_size : '';
					$hasPreviousSize = $orderedSize !== null && $orderedSize !== '';
				?>
				<div class="shop-line">
					<div class="shop-line-item">
						<div class="shop-line-thumb-wrap">
							<img class="shop-line-thumb" src="{{ $itemImage }}" alt="{{ $clothes->clothes_type }}" />
						</div>
						<div class="shop-line-copy">
							<div class="shop-line-name">{{ $clothes->clothes_type }}</div>
							<div class="shop-line-meta">{{ (int) ($clothes->accessory ?? 0) === 1 ? __('app.cart.accessory') : __('app.cart.clothes') }} {{ __('app.cart.for') }} {{ $uniformDisplayName }}</div>
							@if($hasPreviousSize)
							<div class="shop-line-hint">{{ __('app.cart.previous_size') }} {{ $orderedSize }}</div>
							@elseif($clothes->clothes_size)
							<div class="shop-line-hint">{{ __('app.cart.available_rule') }} {{ $clothes->clothes_size }}</div>
							@else
							<div class="shop-line-hint">{{ __('app.cart.no_size') }}</div>
							@endif
						</div>
					</div>

					<?php
						$maxQuantity = isset($clothes->max_quantity) ? $clothes->max_quantity : null;
						$cartQuantity = isset($clothes->cart_quantity) ? (int) $clothes->cart_quantity : 1;
						$qtyInputId = 'qty_' . $clothes->clothes_slug;
					?>
					<div class="shop-line-selection">
						<div class="shop-controls">
							@if($clothes->clothes_size == '')
								<div class="shop-checkbox">
									<input class="form-check-input" type="checkbox" id="{{$inputId}}" {{ $inCart ? "checked" : "" }}>
									<label class="form-check-label" for="{{$inputId}}">{{ __('app.cart.add_this_item') }}</label>
								</div>
							@elseif($clothes->clothes_size == 'FIX')
								<label class="label_">{{ __('app.cart.size') }}</label>
								<input class="form-control" type="text" id="{{$inputId}}" value="{{$clothes->clothes_size}}" readonly />
							@else
								<?php $size_check = str_replace('-','',str_replace(' ','',$clothes->clothes_size)); ?>
								@if(is_numeric($size_check))
									<label class="label_">{{ __('app.cart.size') }}</label>
									<input class="form-control" type="text" id="{{$inputId}}" placeholder="{{$clothes->clothes_size}}" value="{{ is_string($cartValue) ? $cartValue : $orderedSize }}" />
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
										<label class="label_">{{ __('app.cart.size') }}</label>
										<select class="form-control" id="{{$inputId}}" {{ strtolower($clothes->clothes_type) == 'accessories' ? 'multiple' : '' }}>
											<option value="">{{ __('app.cart.choose_size') }}</option>
											@foreach($size_array as $key => $size)
												@if($key >= $start)
													<option value="{{$size}}" {{ (is_array($cartValue) && in_array($size, $cartValue)) || (is_string($cartValue) && $cartValue == $size) ? "selected" : ($orderedSize == $size ? "selected" : "") }}>{{$size}}</option>
													@if($key == $end)
														<?php break; ?>
													@endif
												@endif
											@endforeach
										</select>
									@else
										<label class="label_">{{ __('app.cart.options') }}</label>
										<?php $options = explode("|", $clothes->clothes_size); ?>
										<select class="form-control" id="{{$inputId}}" {{ strtolower($clothes->clothes_type) == 'accessories' ? 'multiple' : '' }}>
											<option value="">{{ __('app.cart.choose') }}</option>
											@foreach($options as $option)
												<?php $opt = trim($option); ?>
												<option value="{{ $opt }}" {{ (is_array($cartValue) && in_array($opt, $cartValue)) || (is_string($cartValue) && $cartValue == $opt) ? "selected" : ($orderedSize == $opt ? "selected" : "") }}>{{ $opt }}</option>
											@endforeach
										</select>
									@endif
								@endif
							@endif
							<div class="shop-qty-row">
								<label class="label_" for="{{ $qtyInputId }}" style="margin: 0;">{{ __('app.cart.quantity') }}</label>
								<input class="form-control shop-qty-input" type="number" id="{{ $qtyInputId }}"
									min="1" step="1"
									@if($maxQuantity !== null) max="{{ $maxQuantity }}" @endif
									value="{{ $maxQuantity !== null ? min($cartQuantity, $maxQuantity) : $cartQuantity }}"
									aria-label="{{ __('app.cart.quantity_for', ['item' => $clothes->clothes_type]) }}" />
								@if($maxQuantity !== null)
								<span class="shop-qty-max">{{ __('app.cart.maximum', ['count' => $maxQuantity]) }}</span>
								@endif
							</div>
						</div>
					</div>

					<div class="shop-line-status">
						<span class="shop-status-badge{{ $inCart ? ' is-in-cart' : '' }}">{{ $inCart ? __('app.cart.in_cart') : __('app.cart.not_added') }}</span>
						@if($cartValue)
						<div class="shop-status-note">
							@if(is_array($cartValue))
								{{ implode(', ', $cartValue) }}
							@else
								{{ $cartValue }}
							@endif
						</div>
						@endif
					</div>

					<div class="shop-line-actions">
						<a href="#" class="shop-line-action-btn shop-line-action-add cart-add" data-uniform-id="{{ $clothes->uniforms_id }}" data-clothes-slug="{{ $clothes->clothes_slug }}" data-input="#{{$inputId}}" aria-label="{{ $inCart ? __('app.cart.update_item') : __('app.cart.add_item') }}" title="{{ $inCart ? __('app.cart.update_item') : __('app.cart.add_item') }}">
							<i class="fa fa-plus" aria-hidden="true"></i>
						</a>
						<a href="#" class="shop-line-action-btn shop-line-action-remove cart-remove{{ $inCart ? '' : ' disabled' }}" data-uniform-id="{{ $clothes->uniforms_id }}" data-clothes-slug="{{ $clothes->clothes_slug }}" aria-label="{{ __('app.cart.remove_item') }}" title="{{ __('app.cart.remove_item') }}">
							<i class="fa fa-minus" aria-hidden="true"></i>
						</a>
					</div>
				</div>
			@endforeach
		</div>

		<div class="shop-board-footer">
			<div class="shop-policy">
				<i class="fa fa-check-square" aria-hidden="true"></i>
				<span>{{ __('app.cart.policy') }}</span>
			</div>
		</div>
	</div>

	<div class="shop-summary-card">
		<div class="shop-summary-head">
			<div class="shop-summary-title">{{ __('app.cart.summary') }}</div>
			<div class="shop-summary-subtitle">{{ __('app.cart.summary_hint') }}</div>
		</div>

		<div class="shop-summary-totals">
			<div class="shop-summary-total-box">
				<div class="shop-summary-total-label">{{ __('app.cart.this_uniform') }}</div>
				<div class="shop-summary-total-value">{{ $currentUniformCartCount }}</div>
			</div>
			<div class="shop-summary-total-box">
				<div class="shop-summary-total-label">{{ __('app.cart.all_items') }}</div>
				<div class="shop-summary-total-value">{{ $cartCount }}</div>
			</div>
		</div>

		@if(count($cartItems))
		<div class="shop-cart-preview shop-cart-preview-side">
			<div class="shop-cart-preview-title">{{ __('app.cart.current_cart') }}</div>
			<div class="shop-cart-preview-list">
				@foreach($cartItems as $item)
				<div class="shop-cart-preview-row">
					<div class="shop-cart-preview-main">
						<div class="shop-cart-preview-name">{{ $item['clothes_type'] }}</div>
						<div class="shop-cart-preview-meta">
							@if(is_array($item['size']))
								{{ implode(', ', $item['size']) }}
							@else
								{{ $item['size'] }}
							@endif
						</div>
					</div>
					<span class="shop-cart-preview-qty">&times; {{ isset($item['quantity']) ? (int) $item['quantity'] : 1 }}</span>
					<a href="#" class="shop-cart-remove cart-remove" data-uniform-id="{{ $item['uniforms_id'] }}" data-clothes-slug="{{ $item['clothes_slug'] }}" aria-label="{{ __('app.cart.remove') }}">
						<i class="fa fa-times" aria-hidden="true"></i>
					</a>
				</div>
				@endforeach
			</div>
		</div>
		@else
		<div class="shop-summary-empty">{{ __('app.cart.empty') }}</div>
		@endif

		<div class="shop-summary-footer">
			<a href="#" class="btn btn-brand shop-checkout-btn cart-checkout{{ $cartCount ? '' : ' disabled' }}">{{ !empty($editOrderId) ? __('app.cart.update_order') : __('app.cart.order_now') }} <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
		</div>
	</div>
</div>
