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
});
