// This code will not run if jQuery is not loaded
this.jQuery && (function ($) {

	$(document).ready( function() {

		// Toggle for connection status indicator
		$('#smt-connection-status-toggle').on('click', function(e) {
			$('#smt-connection-status').slideToggle();

			e.preventDefault();
			return false;
		});

		// Run only on settings page for URLs
		if ($('#smt-settings-url-page').length) {

			// Datepicker
			$('#rewrite_before_date').datepicker({
				dateFormat: "yy-mm-dd"
			});

			var $rewrite_change_to  = $('#rewrite_change_to');
			var $rewrite_match_from = $('#rewrite_match_from');

			var $rewrite_example    = $('#preview_match_from');
			var $rewrite_preview    = $('#preview_change_to');

			var updateRewritePreview = function() {
				var user_input = $rewrite_change_to.val();

				var preview = (user_input) ? $rewrite_example.val().replace($rewrite_match_from.val(), user_input) : '';
				$rewrite_preview.val(preview);

				if (user_input.length == 0) {
					// $rewrite_preview.removeClass('valid invalid');
					$rewrite_change_to.removeClass('valid invalid');
				} else if (isValidURL(preview)) {
					// $rewrite_preview.removeClass('invalid').addClass('valid');
					$rewrite_change_to.removeClass('invalid').addClass('valid');
				} else {
					// $rewrite_preview.addClass('invalid').removeClass('valid');
					$rewrite_change_to.addClass('invalid').removeClass('valid');
				}
			}

			// Preview the URL rewrite
			$('#rewrite_change_to').on('keyup', updateRewritePreview);

			// Run once on page init
			updateRewritePreview();
		}

	});

	function isValidURL(input) {
		return /^(https?):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(input);
	}

})(jQuery);
