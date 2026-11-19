Twee.addModule('carousel', '.carousel', function($, items) {

	// The CSS of the items sets the axis and the alignment. The data-carousel attribute can change the options.
	let args = {
		loop: true,
		dragThreshold: 2
	};

	let section = items.closest('section');

	// The thumbnails of a product gallery follow the main carousel
	if (section.hasClass('product_box') && items.closest('.thumbs').length > 0) {
		args.dots = false;
		args.arrows = false;
		args.sync = $('.gallery .images .items', section).get(0);
	}

	let carousel = BetterCarousel(this, args);

	// Play the videos of the selected slide, and pause the videos of the slide that was selected before
	if (items.find('video').length > 0) {

		let videos = [];

		carousel.on('select', () => {
			videos.forEach((video) => video.pause());
			videos = $('video', carousel.slideNodes()[carousel.selectedSlide()]).get();
			videos.forEach((video) => video.play().catch(() => {}));
		});

	}

}, ['BetterCarousel']);
