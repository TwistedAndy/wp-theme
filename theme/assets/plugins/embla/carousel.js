Twee.addModule('carousel', 'html', function($, container) {

	container.on('embla.init', '.carousel', function() {

		let carousel = this,
			items = $(carousel),
			plugins = [];

		if (items.data('embla')) {
			items.trigger('embla.refresh');
			return;
		}

		let section = items.closest('section, header');

		let args = {
			axis: 'x',
			loop: true,
			align: 'start',
			dragFree: false,
			skipSnaps: true,
			dragThreshold: 2,
			slidesToScroll: 1,
			arrows: true,
			dots: true,
			sync: false
		};

		let style = window.getComputedStyle(carousel);

		if (style.flexDirection.indexOf('column') === 0) {
			args.axis = 'y';
		}

		if (style.justifyContent === 'center') {
			args.align = 'center';
		} else if (style.justifyContent === 'flex-end') {
			args.align = 'end';
		} else {
			args.align = 'start';
		}

		items.addClass('embla_container').wrap('<div class="embla embla_dynamic"><div class="embla_viewport"></div></div>');

		if (section.hasClass('product_box') && items.closest('.thumbs').length > 0) {
			args.dots = false;
			args.arrows = false;
			args.sync = $('.gallery .images .items', section);
		}

		let viewport = items.closest('.embla_viewport'),
			wrapper = viewport.closest('.embla'),
			embla = EmblaCarousel(viewport.get(0), args, plugins);

		if (items.data('class')) {
			wrapper.addClass(items.data('class'));
		}

		if (items.find('video').length > 0) {

			embla.on('select', () => {

				let slides = embla.slideNodes(),
					selected = embla.selectedScrollSnap(),
					previous = embla.previousScrollSnap();

				$('video', slides[selected]).each(function() {
					this.play();
				});

				$('video', slides[previous]).each(function() {
					this.pause();
				});

			});

		}

		items.on('embla.refresh', () => {
			embla.reInit(args);
		});

		items.data('embla', embla);

		['init', 'reInit', 'resize', 'select'].forEach((event) => {
			embla.on(event, () => {

				let slides = embla.slideNodes(),
					snaps = embla.scrollSnapList(),
					selected = embla.selectedScrollSnap(),
					scrollNext = embla.canScrollNext(),
					scrollPrev = embla.canScrollPrev();

				if (isNaN(selected)) {
					selected = 0;
				}

				if (snaps.length > 1) {

					if (args.arrows) {

						let target = args.arrows instanceof $ && args.arrows.length > 0 ? args.arrows : wrapper;

						let prevButton = $('.embla_prev', target),
							nextButton = $('.embla_next', target);

						if (prevButton.length === 0) {
							prevButton = $('<button type="button" class="embla_button embla_prev" aria-label="Previous Slide"></button>');
							prevButton.on('click', () => embla.scrollPrev());
							target.append(prevButton);
						}

						if (nextButton.length === 0) {
							nextButton = $('<button type="button" class="embla_button embla_next" aria-label="Next Slide"></button>');
							nextButton.on('click', () => embla.scrollNext());
							target.append(nextButton);
						}

						prevButton.prop('disabled', !scrollPrev);
						nextButton.prop('disabled', !scrollNext);

					}

					if (args.dots) {

						let target = args.dots instanceof $ && args.dots.length > 0 ? args.dots : wrapper;

						let needDots = true,
							dots = $('.embla_dots', target),
							nodes = $('.embla_dot', target);

						if (dots.length > 0 && nodes.length === snaps.length) {
							needDots = false;
						} else {
							dots.remove();
						}

						if (needDots) {
							dots = $('<div class="embla_dots"></div>');
							snaps.forEach((snap, index) => {
								let dot = $('<button type="button" class="embla_dot" aria-label="Go to slide #' + (index + 1) + '"></button>');
								dot.on('click', () => embla.scrollTo(index));
								dots.append(dot);
							});
							target.append(dots);
							nodes = $('.embla_dot', target);
						}

						if (nodes.length > 0) {
							let selectedDot = $(nodes.get(selected));
							nodes.removeClass('is_first is_prev is_current is_next is_last');
							selectedDot.addClass('is_current');
							selectedDot.prev().addClass('is_prev').prev().addClass('is_first');
							selectedDot.next().addClass('is_next').next().addClass('is_last');
						}

					}

				} else {

					$('.embla_button, .embla_dots', wrapper).remove();

				}

				let classMap = {
					y: {
						center: 'is_middle',
						end: 'is_bottom'
					},
					x: {
						center: 'is_center',
						end: 'is_right'
					}
				};

				let classList = 'is_center is_right is_middle is_bottom',
					activeClass = (scrollPrev && scrollNext) ? classMap[args.axis]?.[args.align] : '';

				if (activeClass) {
					viewport.removeClass(classList.replace(activeClass, '')).addClass(activeClass);
				} else {
					viewport.removeClass(classList);
				}

				wrapper.toggleClass('embla_vertical', args.axis === 'y');
				wrapper.toggleClass('embla_horizontal', args.axis === 'x');
				wrapper.toggleClass('embla_dynamic', snaps.length > 1);

				slides.forEach(function(slide, index) {
					if (index === selected) {
						slides[index].classList.add('is_active');
					} else {
						slides[index].classList.remove('is_active');
					}
				});

			});
		});

		embla.on('resize', () => {

			let style = window.getComputedStyle(carousel),
				lastAxis = args.axis,
				lastAlign = args.align;

			if (style.flexDirection.indexOf('column') === 0) {
				args.axis = 'y';
			} else {
				args.axis = 'x';
			}

			if (style.justifyContent === 'center') {
				args.align = 'center';
			} else if (style.justifyContent === 'flex-end') {
				args.align = 'end';
			} else {
				args.align = 'start';
			}

			if (lastAxis !== args.axis || lastAlign !== args.align) {
				embla.reInit(args);
			}

		});

		embla.on('destroy', () => {
			$('.embla_button, .embla_dots', wrapper).remove();
		});

		if (args.sync) {

			let emblaSource = embla,
				emblaTarget = $(args.sync).data('embla'),
				slides = emblaSource.slideNodes(),
				unlock = true;

			if (!emblaTarget) {
				return;
			}

			slides.forEach((slide, index) => {
				slide.addEventListener('click', () => {
					emblaTarget.scrollTo(index);
				});
			});

			emblaTarget.on('select', () => {

				let target = emblaTarget.selectedScrollSnap();

				if (isNaN(target)) {
					return;
				}

				slides.forEach(function(slide, index) {
					if (index === target) {
						slides[index].classList.add('is_active');
					} else {
						slides[index].classList.remove('is_active');
					}
				});

				if (unlock) {
					unlock = false;
					emblaSource.scrollTo(target);
					unlock = true;
				}

			});

			emblaSource.on('init', () => {
				let target = emblaTarget.selectedScrollSnap();
				if (isNaN(target)) {
					emblaSource.scrollTo(target);
				}
			});

			emblaSource.on('select', () => {
				let target = emblaSource.selectedScrollSnap();
				if (unlock && !isNaN(target)) {
					unlock = false;
					emblaTarget.scrollTo(target);
					unlock = true;
				}
			});

		}

	});

	$('.items.carousel', container).trigger('embla.init');

}, ['EmblaCarousel']);