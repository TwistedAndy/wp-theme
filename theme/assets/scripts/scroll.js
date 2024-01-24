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