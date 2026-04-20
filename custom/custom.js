/**
 * Theme Customisations - Main JavaScript
 * 
 * Organized using the Revealing Module Pattern. 
 * This keeps the global scope clean, groups related functionality together, 
 * and makes it easy to maintain as the codebase grows.
 */
var TFCustomApp = (function ($) {
	'use strict';

	// ── Header Module ───────────────────────────────────────────────
	var Header = {
		init: function () {
			var templateContent = $('#added-account-icon').prop('content');
			if (templateContent) {
				var clone = $(templateContent).children().clone();
				$('.sp-header-last').append(clone);
			}
		}
	};

	// ── Product Page Module ─────────────────────────────────────────
	var Product = {
		init: function () {
			this.bindCustomSizing();
		},
		bindCustomSizing: function () {
			var $talle = $('#tf_talle');
			var $wrap = $('#tf_medidas_wrap');

			if ($talle.length > 0 && $wrap.length > 0) {
				var toggleMedidas = function () {
					var isCustomSelected = $talle.val() === 'a_medida';
					$wrap.toggle(isCustomSelected);
					$wrap.find('input').each(function () {
						$(this).prop('required', isCustomSelected);
					});
				};

				$talle.on('change', toggleMedidas);
				toggleMedidas(); // Run once on load
			}
		}
	};

	// ── Checkout Module ─────────────────────────────────────────────
	var Checkout = {
		init: function () {
			this.bindEvents();
		},
		bindEvents: function () {
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
		}
	};

	// ── Public API ──────────────────────────────────────────────────
	return {
		init: function () {
			Header.init();
			Product.init();
			Checkout.init();
		}
	};

})(jQuery);

// Boot up the application when DOM is ready
jQuery(document).ready(function () {
	TFCustomApp.init();
});
