/* Smooth scroll */

jQuery(function($) {

	$(document.body).on('click', 'a[href*="#"]', function(e) {

		if (this.href && this.href.indexOf('#') !== false && this.href.indexOf('#modal_') === false) {

			var link = document.location.protocol + '//' + document.location.hostname + document.location.pathname,
				parts = this.href.split('#'),
				selector = '';

			if (parts.length > 1 && link === parts[0]) {
				selector = parts[1];
			} else if (parts.length === 1) {
				selector = parts[0];
			}

			if (selector) {
				e.preventDefault();
				smoothScrollTo($('#' + selector));
			}

		}

	});

	if (window.location.hash) {
		smoothScrollTo($(window.location.hash));
	}

});


function smoothScrollTo(element, speed) {

	var $ = jQuery,
		wrapper = $('html, body');

	speed = parseInt(speed) || 1000;

	element = $(element);

	if (element.length > 0) {

		var offset = element.offset().top - scrollOffset() - 20;

		if (element.attr('id')) {
			var scroll = wrapper.scrollTop();
			window.location.hash = element.attr('id');
			wrapper.scrollTop(scroll);
		}

		wrapper.stop().animate({
			'scrollTop': offset
		}, speed);

	}

}


function scrollOffset() {

	var header = jQuery('.header_box .header'),
		offset = header.height();

	if (document.body.classList.contains('admin-bar')) {

		var width = window.innerWidth;

		if (width <= 782 && width >= 600) {
			offset += 46;
		} else if (width > 782) {
			offset += 32;
		}

	}

	return offset;

}