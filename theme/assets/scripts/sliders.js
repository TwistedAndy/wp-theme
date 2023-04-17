jQuery(function($) {

	if (typeof Flickity !== 'function') {

		return;

	}

	/* Set an equal height for the slides */

	var sections = $('.cards_box');

	sections.on('init', function() {

		var section = $(this),
			carousel = $('.slider', section),
			slides = carousel.children('.item'),
			buttons = $('.dots > button', section),
			navigation = $('.arrow_prev, .arrow_next, .controls', section);

		var args = {
			wrapAround: true,
			prevNextButtons: false,
			pageDots: false,
			adaptiveHeight: false,
			cellSelector: '.item',
			imagesLoaded: false,
			cellAlign: 'left',
			watchCSS: false
		};

		buttons.first().addClass('active');

		if (carousel.length > 0 && slides.length > 1) {

			var flkty = new Flickity(carousel.get(0), args);

			$('.arrow_next', section).click(function() {
				flkty.next();
			});

			$('.arrow_prev', section).click(function() {
				flkty.previous();
			});

			buttons.click(function() {
				flkty.select($(this).index());
			});

			carousel.on('select.flickity', function() {
				buttons.removeClass('active').eq(flkty.selectedIndex).addClass('active');
			});

			carousel.data('slider', flkty);

			$(window).on('load resize', updateSlider);

			updateSlider();

			setTimeout(updateSlider, 1000);

		}

		function updateSlider() {

			var width = -10;

			slides.each(function() {
				width += this.offsetWidth;
			});

			flkty.options.draggable = carousel.outerWidth() < width;

			if (flkty.options.draggable) {
				navigation.removeAttr('style');
			} else {
				navigation.hide();
			}

			flkty.updateDraggable();

			flkty.resize();

		}

	});

	sections.trigger('init');

});