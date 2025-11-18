Twee.addModule('better', 'section', function($, wrapper) {

	let better = BetterPicture(),
		selectors = 'a[href*=".png"], a[href*=".jpg"], a[href*=".jpeg"], a[href*=".gif"], a[href*=".webp"], a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"], a[href*=".mp4"]';

	wrapper.on('click', selectors, function(e) {

		const args = {
			el: this,
			items: wrapper.find(selectors).get(),
			thumbs: true,
			onUpdate: function(container, activeItem) {

				let embla = activeItem && $(activeItem.element).closest('.carousel').data('embla');

				if (typeof embla !== 'object') {
					return;
				}

				let slides = embla.slideNodes(),
					index = slides.indexOf(activeItem.element);

				if (index > -1 && embla.slidesNotInView().indexOf(index) > -1) {
					embla.scrollTo(index);
				}

			}
		};

		better.open(args);

		e.preventDefault();
		e.stopPropagation();

	});

}, ['BetterPicture']);