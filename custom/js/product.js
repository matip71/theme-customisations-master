/**
 * Product Page Module
 * Variation price sync and custom sizing fields.
 */
(function ($) {
	'use strict';

	/**
	 * Sync the main price display with the selected variation.
	 * - On load: the PHP filter take care of showing the minimum price.
	 * - On variation found: replace the main price with the variation price.
	 * - On variation reset: revert to the original (minimum) price.
	 */
	function bindVariationPrice() {
		var $form = $('form.variations_form');
		if (!$form.length) return;

		var $mainPrice = $('.entry-summary .price').first();
		if (!$mainPrice.length) return;

		var originalPriceHTML = $mainPrice.html();

		$form.on('show_variation', function (e, variation) {
			$mainPrice.html(variation.price_html);
		});

		$form.on('reset_data', function () {
			$mainPrice.html(originalPriceHTML);
		});
	}

	/**
	 * Sync the sticky add-to-cart bar with the selected variation.
	 * - Updates the price shown in the sticky bar.
	 * - Changes the button from "Seleccionar opciones" to "Agregar al carrito"
	 *   when a valid variation is found, and makes it submit the form.
	 * - Reverts everything on variation reset.
	 */
	function bindStickyBar() {
		var $form = $('.product form.variations_form');
		if (!$form.length) return;

		var $stickyPrice = $('.storefront-sticky-add-to-cart__content-price');
		var $stickyBtn = $('.storefront-sticky-add-to-cart__content-button');
		if (!$stickyPrice.length || !$stickyBtn.length) return;

		var originalPriceHTML = $stickyPrice.html();
		var originalBtnText = $stickyBtn.text().trim();
		var originalHref = $stickyBtn.attr('href');

		$form.on('show_variation', function (e, variation) {
			// 1. Update price
			$stickyPrice.html(variation.price_html);

			// 2. If variation is in stock, convert to add-to-cart button
			if (variation.is_in_stock) {
				$stickyBtn
					.text('Agregar al carrito')
					.attr('href', '#')
					.off('click.stickyATC')
					.on('click.stickyATC', function (ev) {
						ev.preventDefault();
						// Submit the main product form
						$form.find('.single_add_to_cart_button').trigger('click');
					});
			}
		});

		$form.on('hide_variation reset_data', function () {
			// Revert price
			$stickyPrice.html(originalPriceHTML);

			// Revert button
			$stickyBtn
				.text(originalBtnText)
				.attr('href', originalHref)
				.off('click.stickyATC');
		});
	}

	/**
	 * Show/hide custom sizing fields based on the selected size.
	 * - Shows fields when "A medida" is selected.
	 * - Hides fields when any other size is selected.
	 * - Makes fields required/optional accordingly.
	 */
	function bindCustomSizing() {
		var $talle = $('#tf_talle');
		var $wrap = $('#tf_medidas_wrap');

		if ($talle.length > 0 && $wrap.length > 0) {
			var toggleMedidas = function (animate) {
				var isCustomSelected = $talle.val() === 'a_medida';
				if (animate) {
					isCustomSelected ? $wrap.slideDown(500) : $wrap.slideUp(500);
				} else {
					$wrap.toggle(isCustomSelected);
				}
				$wrap.find('input').each(function () {
					$(this).prop('required', isCustomSelected);
				});
			};

			$talle.on('change', function () { toggleMedidas(true); });
			toggleMedidas(false); // Set initial state without animation
		}
	}

	$(function () {
		bindVariationPrice();
		bindStickyBar();
		bindCustomSizing();
	});

})(jQuery);
