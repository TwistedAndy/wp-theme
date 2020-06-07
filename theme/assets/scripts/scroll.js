/* Smooth scroll */

jQuery(function($) {

	$('a[href*="#"]').click(function() {

		var href = this.href;

		var link = document.location.protocol + '//' + document.location.hostname + document.location.pathname;

		if (href && href.indexOf('#') !== false) {

			var parts = href.split('#');

			var selector = '';

			if (parts.length > 1 && link === parts[0]) {
				selector = parts[1];
			} else if (parts.length === 1) {
				selector = parts[0];
			}

			if (selector) {
				smoothScrollTo($('#' + selector));
				return false;
			}

		}

	});

	if (window.location.hash) {
		smoothScrollTo($(window.location.hash));
	}

});

function smoothScrollTo(element, speed) {

	var $ = jQuery;

	speed = parseInt(speed) || 1000;

	element = $(element);

	if (element.length > 0) {

		var offset = element.offset().top - 140;

		$('html, body').stop().animate({
			'scrollTop': offset
		}, speed);

	}

}