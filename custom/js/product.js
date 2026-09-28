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

	/**
	 * Measure Tooltip Modal (Mobile & Desktop).
	 * - Desktop: hover shows tooltip bubble (via CSS), click toggles active state.
	 * - Mobile: opens centered modal with a full-screen backdrop overlay.
	 * - Overlay intercepts and absorbs clicks outside the modal to prevent accidental
	 *   interactions with underlying inputs/buttons.
	 */
	function bindMeasureTooltips() {
		var $overlay = $('.tf-measure-overlay');
		if (!$overlay.length) {
			$overlay = $('<div class="tf-measure-overlay"></div>').appendTo('body');
		}

		var closeMeasureTooltips = function () {
			$('.tf-measure-help.tf-is-active').removeClass('tf-is-active');
			$overlay.removeClass('tf-is-active');
			$('body').removeClass('tf-measure-modal-open');
		};

		// Tapping the ⓘ icon / trigger
		$(document).on('click', '.tf-measure-help', function (e) {
			// If click is inside the bubble, don't toggle
			if ($(e.target).closest('.tf-measure-tooltip-bubble').length) {
				return;
			}

			// Prevent parent label from focusing its associated input
			e.preventDefault();
			e.stopPropagation();

			var $help = $(this);
			var wasActive = $help.hasClass('tf-is-active');

			closeMeasureTooltips();

			if (!wasActive) {
				$help.addClass('tf-is-active');
				if (window.innerWidth <= 768) {
					$overlay.addClass('tf-is-active');
					$('body').addClass('tf-measure-modal-open');
				}
			}
		});

		// Prevent clicks inside the bubble from bubbling to parent label or document
		$(document).on('click', '.tf-measure-tooltip-bubble', function (e) {
			e.stopPropagation();
		});

		// Click on overlay to close: absorbs event completely
		$overlay.on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			closeMeasureTooltips();
		});

		// Keyboard ESC to close
		$(document).on('keydown', function (e) {
			if (e.key === 'Escape' || e.keyCode === 27) {
				closeMeasureTooltips();
			}
		});

		// Click outside on desktop
		$(document).on('click', function (e) {
			if (!$(e.target).closest('.tf-measure-help, .tf-measure-overlay').length) {
				closeMeasureTooltips();
			}
		});
	}

	/**
	 * Size Chart Modal.
	 * Opens a lightbox modal showing the standard size chart image.
	 * Triggered by the "Tabla de talles" link rendered by PHP.
	 */
	function bindSizeChartModal() {
		var $modal = $('#tf_size_chart_modal');
		if (!$modal.length) return;

		var $backdrop = $modal.find('.tf-size-chart-modal__backdrop');
		var $closeBtn = $modal.find('.tf-size-chart-modal__close');

		function openModal(e) {
			e.preventDefault();
			$modal.addClass('tf-is-open');
			$('body').addClass('tf-size-chart-open');
		}

		function closeModal() {
			$modal.removeClass('tf-is-open');
			$('body').removeClass('tf-size-chart-open');
		}

		// Open: click on any "Tabla de talles" link
		$(document).on('click', '.tf-size-chart-link', openModal);

		// Close: click × button
		$closeBtn.on('click', closeModal);

		// Close: click backdrop
		$backdrop.on('click', closeModal);

		// Close: ESC key
		$(document).on('keydown', function (e) {
			if ((e.key === 'Escape' || e.keyCode === 27) && $modal.hasClass('tf-is-open')) {
				closeModal();
			}
		});
	}

	$(function () {
		bindVariationPrice();
		bindStickyBar();
		bindCustomSizing();
		bindMeasureTooltips();
		bindSizeChartModal();
	});

})(jQuery);
