/**
 * Variation Tooltip Module
 * Converts variation detail lists into interactive tooltips in cart/checkout/mini-cart.
 */
(function ($) {
	'use strict';

	function initVariationTooltips() {
		var selectors = [
			'.woocommerce-cart-form .variation',
			'.woocommerce-checkout .variation',
			'.woocommerce-mini-cart .variation',
			'.woocommerce-view-order .wc-item-meta',
			'.woocommerce-order-received .wc-item-meta',
			'.woocommerce-order-pay .wc-item-meta'
		].join(', ');

		// Only process elements — skip already-converted tooltips
		$(selectors).each(function () {
			var $container = $(this);
			var leaveTimer;

			var $wrap = $('<span class="tf-variation-tooltip"></span>');
			var $trigger = $('<button type="button" class="tf-variation-trigger" aria-label="Ver medidas" aria-expanded="false"><i class="fas fa-info-circle"></i></button>');
			var $panel = $('<div class="tf-variation-panel" role="tooltip"></div>');

			// Build panel rows from the original element
			if ($container.is('dl') || $container.hasClass('variation')) {
				$container.find('dt').each(function () {
					var label = $(this).text().replace(/:$/, '').trim();
					var value = $(this).next('dd').text().trim();
					$panel.append(
						$('<div class="tf-variation-row"></div>')
							.append($('<span class="tf-variation-key"></span>').text(label))
							.append($('<span class="tf-variation-val"></span>').text(value))
					);
				});
			} else if ($container.is('ul') || $container.hasClass('wc-item-meta')) {
				$container.find('li').each(function () {
					var label = $(this).find('.wc-item-meta-label').text().replace(/:$/, '').trim();
					// WC sometimes wraps the value in a <p>, sometimes it's just a text node
					var $p = $(this).find('p');
					var value = '';

					if ($p.length) {
						value = $p.text().trim();
					} else {
						// Fallback: get all text in the li and strip out the label text
						var fullText = $(this).text().trim();
						var originalLabelText = $(this).find('.wc-item-meta-label').text();
						value = fullText.replace(originalLabelText, '').trim();
					}

					$panel.append(
						$('<div class="tf-variation-row"></div>')
							.append($('<span class="tf-variation-key"></span>').text(label))
							.append($('<span class="tf-variation-val"></span>').text(value))
					);
				});
			}

			$wrap.append($trigger).append($panel);
			$container.replaceWith($wrap);

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
	}

	$(function () {
		initVariationTooltips();
		// Re-run every time WooCommerce refreshes the cart/checkout via AJAX
		$(document.body).on('updated_cart_totals updated_checkout added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded updated_wc_div', initVariationTooltips);
	});

})(jQuery);
