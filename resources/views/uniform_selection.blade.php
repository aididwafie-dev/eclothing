@include('static-layout/header')
@include('static-layout/sidebar')

</br>
<div class="title"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> Order Uniform</div>
<hr>

<div class="containerMain">
	<div class="content">
		<div class="title"><i class="fa fa-shopping-cart" aria-hidden="true"></i> Uniform Shopping Cart</div>
		<hr>
		<div class="uniform-picker">
			@foreach($data['uniforms'] as $uniforms)
				<a href="javascript:void(0);" class="uniform-pill uniform-select" data-uniform-id="{{$uniforms->id}}">
					<span class="uniform-pill-title">
						<?php if ($uniforms->uniform_type != "ACC") { ?>
							{{$uniforms->uniform_type}} {{$uniforms->uniform_name ? ' (' . $uniforms->uniform_name . ')' : ''}}
						<?php } else { ?>
							{{$uniforms->uniform_type}}
						<?php } ?>
					</span>
				</a>
			@endforeach
		</div>

		<div id="clothLoader" class="text-center" style="color: var(--text);margin-top: 2em; display:none;">
			<i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i><br />
			<span style="font-size: 125%;">Loading items....</span>
		</div>
		<div id="loadDataForm" class="loadDataForm"></div>
	</div>
</div>
<!--#### 3 div open in sidebar ####-->
</div>
</div>
</div>
<!--#### 3 div open in sidebar ####-->
<script type="text/javascript">
	function loadDynamicForm(formTypeId) {
		$("#clothLoader").show();
		uniform_id = formTypeId;

		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
			}
		});
		$.ajax({
			type: 'post',
			url: '/load-uniform-data',
			data: {
				uniform_id: uniform_id,
				last_uniform: false
			},
			success: function(data) {
				$("#loadDataForm").html(data);
				$("#clothLoader").hide();
			}
		});
	}

	$(document).ready(function() {
		var enable = '<?php if(session()->has('uniform_ordered')){echo session()->get('uniform_ordered');}?>';
		var defaultId = $('.uniform-select:first').data('uniform-id');
		if (enable) {
			var next = $('.uniform-select[data-uniform-id="' + enable + '"]').next('.uniform-select').data('uniform-id');
			defaultId = next ? next : defaultId;
		}

		$('.uniform-select').removeClass('active');
		$('.uniform-select[data-uniform-id="' + defaultId + '"]').addClass('active');
		loadDynamicForm(defaultId);

		$(document).on('click', '.uniform-select', function() {
			var id = $(this).data('uniform-id');
			$('.uniform-select').removeClass('active');
			$(this).addClass('active');
			loadDynamicForm(id);
		});

		$(document).on('click', '.cart-add', function(e) {
			e.preventDefault();
			var $btn = $(this);
			var uniformsId = $btn.data('uniform-id');
			var clothesSlug = $btn.data('clothes-slug');
			var inputSelector = $btn.data('input');
			var sizeVal = null;

			if (inputSelector) {
				var $input = $(inputSelector);
				if ($input.length) {
					if ($input.is('select[multiple]')) {
						sizeVal = $input.val() || [];
					} else if ($input.is(':checkbox')) {
						sizeVal = $input.is(':checked') ? 'YES' : '';
					} else {
						sizeVal = $input.val();
					}
				}
			}

			// Quantity is capped server-side by the entitlement scale; this just
			// sends what the user picked.
			var quantityVal = 1;
			var $qty = $('#qty_' + clothesSlug);
			if ($qty.length) {
				quantityVal = parseInt($qty.val(), 10);
				if (isNaN(quantityVal) || quantityVal < 1) {
					quantityVal = 1;
				}
			}

			$.ajax({
				type: 'post',
				url: '/uniform-cart/add',
				data: {
					uniforms_id: uniformsId,
					clothes_slug: clothesSlug,
					size: sizeVal,
					quantity: quantityVal
				},
				success: function(resp) {
					if (resp && resp.redirect) {
						window.location.href = resp.redirect;
						return;
					}
					loadDynamicForm(uniformsId);
				},
				error: function(xhr) {
					var message = 'Tidak dapat menambah item ini.';
					if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
						message = xhr.responseJSON.message;
					}
					if (window.showAppPopup) {
						window.showAppPopup(message, 'danger');
					}
				}
			});
		});

		$(document).on('click', '.cart-remove', function(e) {
			e.preventDefault();
			var $btn = $(this);
			if ($btn.hasClass('disabled')) {
				return;
			}
			var uniformsId = $btn.data('uniform-id');
			var clothesSlug = $btn.data('clothes-slug');
			$.ajax({
				type: 'post',
				url: '/uniform-cart/remove',
				data: {
					uniforms_id: uniformsId,
					clothes_slug: clothesSlug
				},
				success: function() {
					loadDynamicForm(uniformsId);
				}
			});
		});

		$(document).on('click', '.cart-checkout', function(e) {
			e.preventDefault();
			var $btn = $(this);
			if ($btn.hasClass('disabled')) {
				return;
			}
			$btn.prop('disabled', true);
			$.ajax({
				type: 'post',
				url: '/uniform-cart/checkout',
				data: {},
				success: function(resp) {
					if (resp && resp.redirect) {
						window.location.href = resp.redirect;
						return;
					}
					window.location.href = '/user/ordered-uniform';
				},
				// The server refuses a checkout that would overwrite an order
				// which has left Pending; show its reason rather than failing
				// silently.
				error: function(xhr) {
					var message = (xhr.responseJSON && xhr.responseJSON.message)
						? xhr.responseJSON.message
						: 'Sorry, your order could not be saved. Please try again.';
					if (window.showAppPopup) {
						window.showAppPopup(message, 'danger', { autoClose: false });
					} else {
						alert(message);
					}
				},
				complete: function() {
					$btn.prop('disabled', false);
				}
			});
		});
	});

</script>
</body>

</html>

<?php
	session()->put('uniform_ordered', '');
?>
