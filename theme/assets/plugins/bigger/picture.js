Twee.addModule('bigger', 'html', function($, container) {

	// Images and videos by the file extension, YouTube and Vimeo videos by the address
	const files = /\.(jpe?g|png|gif|webp|avif|mp4|webm|mov)$/i,
		videos = /youtube\.com\/(watch|shorts|embed)|youtu\.be\/|vimeo\.com\/(video\/)?\d/i;

	let bigger;

	// Other links and downloads keep working as links
	function isMedia(link) {
		return !link.hasAttribute('download') && (files.test(link.pathname) || videos.test(link.href));
	}

	// Hidden links, such as the ones in closed tabs, are left out of the gallery
	function isVisible(link) {
		return link.getClientRects().length > 0;
	}

	// The media links of the section of the clicked link, one per address
	function getItems(current) {

		let section = $(current).closest('section, header, footer, aside');

		if (section.length === 0) {
			return [current];
		}

		let links = $('a[href]', section).get().filter((link) => link === current || (isMedia(link) && isVisible(link))),
			items = new Map();

		// The clicked link takes the place of other links to the same address
		links.forEach((link) => {
			if (link === current || !items.has(link.href)) {
				items.set(link.href, link);
			}
		});

		return Array.from(items.values());

	}

	// Scroll a carousel to the shown item when its slide is out of view
	function syncCarousel(wrapper, activeItem) {

		let embla = $(activeItem.element).closest('.carousel').data('embla');

		if (!embla) {
			return;
		}

		let index = embla.slideNodes().findIndex((slide) => slide.contains(activeItem.element));

		if (index > -1 && !embla.slidesInView().includes(index)) {
			embla.goTo(index);
		}

	}

	container.on('click', 'a[href]', function(e) {

		// Modified clicks and clicks handled by other scripts are left to the browser
		if (e.isDefaultPrevented() || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || !isMedia(this)) {
			return;
		}

		e.preventDefault();

		bigger = bigger || BiggerPicture();

		bigger.open({
			current: this,
			items: getItems(this),
			thumbs: true,
			history: true,
			onUpdate: syncCarousel
		});

	});

}, ['BiggerPicture']);
