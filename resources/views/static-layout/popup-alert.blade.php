@php
	$popupFlashMessage = session('message');
	$popupFlashClass = session('alert-class', 'alert-info');
	$popupValidationMessage = $errors->any() ? $errors->first() : '';
@endphp

<div class="modal fade app-popup-modal" id="appPopupModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm" role="document">
		<div class="modal-content app-popup-content">
			<div class="modal-body app-popup-body">
				<div class="app-popup-icon" id="appPopupIcon">
					<i class="fa fa-info-circle" aria-hidden="true"></i>
				</div>
				<div class="app-popup-title" id="appPopupTitle">Notification</div>
				<div class="app-popup-message" id="appPopupMessage"></div>
				<div class="app-popup-actions">
					<button type="button" class="btn btn-brand" data-dismiss="modal">OK</button>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	(function(window, $) {
		if (!$) {
			return;
		}

		var popupTimer = null;

		function normalizePopupType(type) {
			var normalized = (type || 'info').toString().toLowerCase();
			if (normalized === 'error') {
				normalized = 'danger';
			}
			if (['success', 'danger', 'warning', 'info'].indexOf(normalized) === -1) {
				normalized = 'info';
			}
			return normalized;
		}

		function typeFromAlertClass(alertClass) {
			var normalized = (alertClass || '').toString().toLowerCase();
			if (normalized.indexOf('success') !== -1) {
				return 'success';
			}
			if (normalized.indexOf('danger') !== -1 || normalized.indexOf('error') !== -1) {
				return 'danger';
			}
			if (normalized.indexOf('warning') !== -1) {
				return 'warning';
			}
			return 'info';
		}

		function popupTitle(type) {
			if (type === 'success') {
				return 'Success';
			}
			if (type === 'danger') {
				return 'Error';
			}
			if (type === 'warning') {
				return 'Warning';
			}
			return 'Notification';
		}

		function popupIcon(type) {
			if (type === 'success') {
				return 'fa-check-circle';
			}
			if (type === 'danger') {
				return 'fa-times-circle';
			}
			if (type === 'warning') {
				return 'fa-exclamation-triangle';
			}
			return 'fa-info-circle';
		}

		window.hideAppPopup = function() {
			if (popupTimer) {
				clearTimeout(popupTimer);
				popupTimer = null;
			}
			$('#appPopupModal').modal('hide');
		};

		window.showAppPopup = function(message, type, options) {
			options = options || {};
			var normalizedType = normalizePopupType(type);
			var safeMessage = $.trim((message || '').toString());

			if (!safeMessage) {
				return;
			}

			if (popupTimer) {
				clearTimeout(popupTimer);
				popupTimer = null;
			}

			$('#appPopupModal')
				.removeClass('app-popup-success app-popup-danger app-popup-warning app-popup-info')
				.addClass('app-popup-' + normalizedType);

			$('#appPopupIcon i')
				.attr('class', 'fa ' + popupIcon(normalizedType))
				.attr('aria-hidden', 'true');

			$('#appPopupTitle').text(options.title || popupTitle(normalizedType));
			$('#appPopupMessage').html(safeMessage.replace(/\n/g, '<br>'));

			$('#appPopupModal').modal({
				backdrop: 'static',
				keyboard: true,
				show: true
			});

			if (options.autoClose !== false) {
				popupTimer = setTimeout(function() {
					window.hideAppPopup();
				}, options.duration || 3200);
			}
		};

		$(document).ready(function() {
			var flashMessage = <?php echo json_encode($popupFlashMessage); ?>;
			var flashClass = <?php echo json_encode($popupFlashClass); ?>;
			var validationMessage = <?php echo json_encode($popupValidationMessage); ?>;

			if (flashMessage) {
				window.showAppPopup(flashMessage, typeFromAlertClass(flashClass));
			} else if (validationMessage) {
				window.showAppPopup(validationMessage, 'danger', { autoClose: false });
			}
		});
	})(window, window.jQuery);
</script>
