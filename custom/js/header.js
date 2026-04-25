/**
 * Header Module
 * Injects account icon into the Storefront header.
 */
(function ($) {
	'use strict';

	$(function () {
		var templateContent = $('#added-account-icon').prop('content');
		if (templateContent) {
			var clone = $(templateContent).children().clone();
			$('.sp-header-last').append(clone);
		}
	});

})(jQuery);
