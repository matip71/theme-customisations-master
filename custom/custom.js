jQuery(document).ready(function ($) {
	// Running inside jQuery.ready() guarantees the DOM is fully built

	// ── Header: move account icon into the sticky header ──────────
	var templateContent = $('#added-account-icon').prop('content');
	if (templateContent) {
		var clone = $(templateContent).children().clone();
		$('.sp-header-last').append(clone);
	}

	// ── Product page: toggle "A medida" measure fields ─────────────
	var $talle = $('#tf_talle');
	var $wrap = $('#tf_medidas_wrap');

	if ($talle.length > 0 && $wrap.length > 0) {
		function toggleMedidas() {
			var isCustomSelected = $talle.val() === 'a_medida';
			$wrap.toggle(isCustomSelected);
			$wrap.find('input').each(function () {
				$(this).prop('required', isCustomSelected);
			});
		}

		$talle.on('change', toggleMedidas);
		toggleMedidas(); // Run once on load to reflect any pre-selected value.
	}

	// ── Cart: variation dl → tooltip ───────────────────────────────
	function initCartTooltips() {
		// Only process raw <dl> elements — skip already-converted tooltips
		$('.woocommerce-cart-form .variation').each(function () {
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

			function openPanel() {
				clearTimeout(leaveTimer);
				$('.tf-variation-panel.tf-is-open').not($panel).removeClass('tf-is-open');
				$('.tf-variation-trigger[aria-expanded="true"]').not($trigger).attr('aria-expanded', 'false');
				$panel.addClass('tf-is-open');
				$trigger.attr('aria-expanded', 'true');
			}

			function closePanel() {
				$panel.removeClass('tf-is-open');
				$trigger.attr('aria-expanded', 'false');
			}

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

	// Run on initial page load
	initCartTooltips();

	// Re-run every time WooCommerce refreshes the cart via AJAX
	$(document.body).on('updated_cart_totals', initCartTooltips);

	// ── Checkout: Fix abrupt hide on unchecked ─────────────────────
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
