Twee.addModule('carousel', 'html', function($, container) {

	$('.items.carousel', container).each(function() {

		if (!Twee.runOnce(this, 'carousel', 500)) {
			return;
		}

		let wrapper = $(this),
			carousel = wrapper.data('carousel'),
			plugins = {};

		let args = {
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
			},
			on: {
				'ready change': function(carousel) {
					carousel.slides.forEach(function(slide) {
						if (slide.el.ariaHidden) {
							$('a', slide.el).attr('tabindex', -1);
						} else {
							$('a', slide.el).removeAttr('tabindex');
						}
					});
				}
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

		wrapper.on('refresh', function() {
			carousel.reInit(args, plugins);
		});

		if (typeof carousel === 'object') {

			carousel.reInit(args, plugins);

		} else {

			carousel = new Carousel(wrapper.get(0), args, plugins);

			wrapper.data('carousel', carousel);

		}

	});

}, ['Carousel'], true);