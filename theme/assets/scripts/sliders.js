jQuery(function($) {

	if (typeof Flickity !== 'undefined') {

		/* Set an equal height for the slides */

		Flickity.prototype._createResizeClass = function() {
			this.element.classList.add('flickity-resize');
		};

		Flickity.createMethods.push('_createResizeClass');

		var resize = Flickity.prototype.resize;

		Flickity.prototype.resize = function() {
			this.element.classList.remove('flickity-resize');
			resize.call(this);
			this.element.classList.add('flickity-resize');
		};

		$('.slider_box').each(function() {

			var wrapper = $(this),
				carousel = $('.slider', wrapper),
				buttons = $('.dots > span', wrapper),
				navigation = $('.arrow_prev, .arrow_next, .dots', wrapper),
				slides = carousel.children('.item');

			var args = {
				wrapAround: true,
				prevNextButtons: false,
				pageDots: false,
				adaptiveHeight: false,
				cellSelector: '.item',
				imagesLoaded: true,
				cellAlign: 'left',
				watchCSS: false
			};

			buttons.first().addClass('active');

			if (carousel.length > 0 && slides.length > 1) {

				var flkty = new Flickity(carousel.get(0), args);

				$('.arrow_next', wrapper).click(function() {
					flkty.next();
				});

				$('.arrow_prev', wrapper).click(function() {
					flkty.previous();
				});

				buttons.click(function() {
					flkty.select($(this).index());
				});

				carousel.on('select.flickity', function() {
					buttons.removeClass('active').eq(flkty.selectedIndex).addClass('active');
				});

				$(window).on('load resize', init);

				init();

				function init() {

					flkty.options.draggable = carousel.outerWidth() < slides.outerWidth() * slides.length;

					if (flkty.options.draggable) {
						navigation.removeAttr('style');
					} else {
						navigation.hide();
					}

					flkty.updateDraggable();

					flkty.resize();

				}

			}

		});

	}

});