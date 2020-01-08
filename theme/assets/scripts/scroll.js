/* Smooth scroll */

jQuery(function($) {

	$('a[href*="#"]').click(function() {

		let href = this.href;

		let link = document.location.protocol + "//" + document.location.hostname + document.location.pathname;

		if (href && href.indexOf('#') !== false) {

			let parts = href.split('#');
			
			let selector = '';

			if (parts.length > 1 && link === parts[0]) {
				selector = parts[1];
			} else if (parts.length === 1) {
				selector = parts[0];
			}

			if (selector) {
				scrollTo($("#" + selector));
				return false;
			}

		}

	});

	let location = document.location.href;

	if (location.indexOf('#') !== false) {

		location = location.split('#');

		if (location[1]) {

			scrollTo($('#' + location[1]));

		}

	}

	function scrollTo(element) {

		let $ = jQuery;

		if (element.length > 0) {

			let offset = element.offset().top - 60;

			$('html, body').stop().animate({
				'scrollTop': offset
			}, 1000);

		}

	}


});