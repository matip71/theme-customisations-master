/**
 * Checkout Module
 * Smooth slide-up animations for WooCommerce checkout toggles.
 */
(function ($) {
	'use strict';

	$(function () {
		// Fix abrupt hide on unchecked
		$(document).on('change', '.woocommerce-checkout #ship-to-different-address-checkbox', function () {
			if (!$(this).is(':checked')) {
				// WooCommerce performs an abrupt .hide(), we counter it by showing and sliding up smoothly.
				$('div.shipping_address').show().slideUp(300);
			}
		});

		$(document).on('change', '.woocommerce-checkout #createaccount', function () {
			if (!$(this).is(':checked')) {
				$('div.create-account').show().slideUp(300);
			}
		});
	});

})(jQuery);
