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
			if (variation.price_html) {
				$mainPrice.html(variation.price_html);
			}
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
			// 1. Update price (only if WooCommerce sends a non-empty value)
			if (variation.price_html) {
				$stickyPrice.html(variation.price_html);
			}

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
	 *
	 * Works with either select that controls talle:
	 *   - #tf_talle           → our custom select (talle is NOT a variation)
	 *   - [attribute_talle]   → WooCommerce's select (talle IS a variation)
	 *
	 * Shows the "medidas" panel when "A medida" is selected,
	 * hides it otherwise. Makes measure inputs required accordingly.
	 */
	function bindCustomSizing() {
		var $wrap = $('#tf_medidas_wrap');
		if (!$wrap.length) return;

		// Pick whichever select is present.
		var $talle = $('#tf_talle');
		if (!$talle.length) {
			$talle = $('select[data-attribute_name="attribute_talle"]');
		}
		if (!$talle.length) return;

		/**
		 * Normalise raw value from either select to check for "a medida".
		 * Our custom select sends "a_medida".
		 * WooCommerce sends "A medida" (custom attr) or "a-medida" (taxonomy).
		 */
		var isAMedida = function (val) {
			if (!val) return false;
			return val.toLowerCase().replace(/[\s\-_]+/g, '') === 'amedida';
		};

		var toggleMedidas = function (animate) {
			var show = isAMedida($talle.val());
			if (animate) {
				show ? $wrap.slideDown(500) : $wrap.slideUp(500);
			} else {
				$wrap.toggle(show);
			}
			$wrap.find('input').each(function () {
				$(this).prop('required', show);
			});
		};

		$talle.on('change', function () { toggleMedidas(true); });
		toggleMedidas(false); // Set initial state without animation

		// On WooCommerce variation reset, also reset the medidas panel.
		$('form.variations_form').on('reset_data', function () {
			toggleMedidas(false);
		});
	}

	$(function () {
		bindVariationPrice();
		bindStickyBar();
		bindCustomSizing();
	});

})(jQuery);
