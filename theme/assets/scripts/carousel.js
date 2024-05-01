jQuery(document).on('tw_init', '.posts_box, .content_box', function(e, $) {

	if (typeof Carousel !== 'function') {
		return;
	}

	var section = $(this),
		wrappers = section.find('.carousel');

	wrappers.each(function() {

		if (runOnce(this, 'carousel', 500)) {
			return;
		}

		var wrapper = $(this),
			carousel = wrapper.data('carousel'),
			plugins = {};

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

			if (wrapper.hasClass('gallery-columns-1') && typeof Thumbs !== 'undefined') {

				plugins = {Thumbs};

				args.Dots = false;

				args.Thumbs = {
					type: 'classic'
				};

			}

		}

		if (typeof carousel === 'object') {

			carousel.reInit(args, plugins);

		} else {

			carousel = new Carousel(this, args, plugins);

			wrapper.data('carousel', carousel);

		}

	});

});