/**
 * Theme Customisations - Cart JavaScript
 * Separated module for all Cart-related functionality.
 */
var TFVariationTooltipModule = (function ($) {
	'use strict';

	var initVariationTooltips = function () {
		// Only process raw <dl> elements — skip already-converted tooltips
		$('.woocommerce-cart-form .variation, .woocommerce-checkout .variation').each(function () {
			var $dl = $(this);
			var leaveTimer;

			var $wrap = $('<span class="tf-variation-tooltip"></span>');
			var $trigger = $('<button type="button" class="tf-variation-trigger" aria-label="Ver medidas" aria-expanded="false"><i class="fas fa-info-circle"></i></button>');
			var $panel = $('<div class="tf-variation-panel" role="tooltip"></div>');

			// Build panel rows from the original dl
			$dl.find('dt').each(function () {
				var label = $(this).text().replace(/:$/, '');
				var value = $(this).next('dd').text().trim();
				$panel.append(
					$('<div class="tf-variation-row"></div>')
						.append($('<span class="tf-variation-key"></span>').text(label))
						.append($('<span class="tf-variation-val"></span>').text(value))
				);
			});

			$wrap.append($trigger).append($panel);
			$dl.replaceWith($wrap);

			var openPanel = function () {
				clearTimeout(leaveTimer);
				$('.tf-variation-panel.tf-is-open').not($panel).removeClass('tf-is-open');
				$('.tf-variation-trigger[aria-expanded="true"]').not($trigger).attr('aria-expanded', 'false');
				$panel.addClass('tf-is-open');
				$trigger.attr('aria-expanded', 'true');
			};

			var closePanel = function () {
				$panel.removeClass('tf-is-open');
				$trigger.attr('aria-expanded', 'false');
			};

			// Hover (desktop) — 200ms delay prevents accidental close
			$trigger.on('mouseenter', openPanel);
			$trigger.on('mouseleave', function () { leaveTimer = setTimeout(closePanel, 200); });
			$panel.on('mouseenter', function () { clearTimeout(leaveTimer); });
			$panel.on('mouseleave', function () { leaveTimer = setTimeout(closePanel, 200); });

			// Click toggle (mobile & keyboard)
			$trigger.on('click', function (e) {
				e.stopPropagation();
				clearTimeout(leaveTimer);
				$panel.hasClass('tf-is-open') ? closePanel() : openPanel();
			});
		});

		// Re-register the outside-click listener once (avoid duplicates)
		$(document).off('click.tf-tooltip').on('click.tf-tooltip', function () {
			$('.tf-variation-panel.tf-is-open').removeClass('tf-is-open');
			$('.tf-variation-trigger[aria-expanded="true"]').attr('aria-expanded', 'false');
		});
	};

	// ── Public API ──────────────────────────────────────────────────
	return {
		init: function () {
			initVariationTooltips();
			// Re-run every time WooCommerce refreshes the cart/checkout via AJAX
			$(document.body).on('updated_cart_totals updated_checkout', initVariationTooltips);
		}
	};

})(jQuery);

// Boot up the application when DOM is ready
jQuery(document).ready(function () {
	TFVariationTooltipModule.init();
});
