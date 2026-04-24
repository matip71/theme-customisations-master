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
		bindCustomSizing();
	});

})(jQuery);
