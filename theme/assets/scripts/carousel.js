jQuery(function($) {

	var sections = $('.posts_box');

	sections.on('init', function() {

		if (typeof Carousel !== 'function') {
			return;
		}

		var section = $(this),
			wrappers = section.find('.carousel'),
			plugins = {};

		wrappers.each(function() {

			var wrapper = $(this),
				carousel = wrapper.data('carousel');

			if (typeof carousel === 'object') {

				carousel.reInit();

			} else {

				var args = {
					infinite: true,
					center: false,
					transition: 'slide',
					slidesPerPage: 1,
					classes: {
						container: 'carousel',
						viewport: 'carousel-viewport',
						track: 'carousel-track',
						slide: 'item'
					},
					Dots: {
						classes: {
							list: 'carousel-dots',
							isDynamic: 'is-dynamic',
							hasDots: 'has-dots',
							dot: 'dot',
							isBeforePrev: 'is-before-prev',
							isPrev: 'is-prev',
							isCurrent: 'is-current',
							isNext: 'is-next',
							isAfterNext: 'is-after-next'
						},
						dotTpl: '<button type="button" data-carousel-page="%i" aria-label="{{GOTO}}"></button>',
						dynamicFrom: 3,
						minCount: 3
					},
					Navigation: {
						classes: {
							container: 'carousel-nav',
							button: 'carousel-button',
							isNext: 'is-next',
							isPrev: 'is-prev'
						},
						nextTpl: '',
						prevTpl: ''
					}
				};

				if (wrapper.hasClass('gallery')) {

					args.classes.slide = 'gallery-item';

					args.Dots = false;

					if (typeof Thumbs !== 'undefined') {

						plugins = {Thumbs};

						args.Thumbs = {
							type: 'classic'
						}

					}

				}

				carousel = new Carousel(this, args);

				wrapper.data('carousel', carousel);

			}

		});

	});

	sections.trigger('init');

});