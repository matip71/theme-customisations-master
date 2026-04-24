/**
 * Account Module
 * Mobile login/register tab switcher for the My Account page.
 */
(function ($) {
	'use strict';

	$(function () {
		var $loginContainer = $('#customer_login');
		if ($loginContainer.length > 0 && !$('.tf-auth-switch').length) {
			var switcherHTML = '\
				<div class="tf-auth-switch">\
					<button class="tf-auth-btn active" data-target="login">Acceder</button>\
					<button class="tf-auth-btn" data-target="register">Registrarse</button>\
				</div>\
			';
			$loginContainer.before(switcherHTML);

			// Initial state setup for mobile
			$loginContainer.addClass('tf-show-login');

			$('.tf-auth-btn').on('click', function (e) {
				e.preventDefault();
				$('.tf-auth-btn').removeClass('active');
				$(this).addClass('active');

				if ($(this).data('target') === 'login') {
					$loginContainer.removeClass('tf-show-register').addClass('tf-show-login');
				} else {
					$loginContainer.removeClass('tf-show-login').addClass('tf-show-register');
				}
			});
		}
	});

})(jQuery);
