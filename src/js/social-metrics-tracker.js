// This code will not run if jQuery is not loaded
this.jQuery && (function ($) {

	$(document).ready( function() {

		$("#smt-connection-status-toggle").on('click', function(e) {
			$("#smt-connection-status").slideToggle();

			e.preventDefault();
			return false;
		});

	});

})(jQuery);
