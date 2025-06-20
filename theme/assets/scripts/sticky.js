Twee.addModule('sticky', 'html', function($) {

	let elements = document.querySelectorAll('.header_box.is_sticky'),
		header = $('.header_box').get(0),
		isAdmin = document.body.classList.contains('admin-bar');

	const handleScroll = Twee.throttle(function() {

		let offsetScroll = 0,
			offsetTop = 0,
			offsetBottom = 0,
			items = [],
			itemsTop = [],
			itemsBottom = [];

		if (isAdmin) {

			if (window.innerWidth <= 782 && window.innerWidth >= 600) {
				offsetTop += 46;
			} else if (window.innerWidth > 782) {
				offsetTop += 32;
			}

			offsetScroll = offsetTop;

		}

		elements.forEach(function(element) {

			let styles = window.getComputedStyle(element, null),
				position = styles.getPropertyValue('position');

			if (position !== 'fixed' && position !== 'sticky') {
				return;
			}

			let bottom = styles.getPropertyValue('bottom'),
				top = styles.getPropertyValue('top'),
				rect = element.getBoundingClientRect();

			if (rect.height > 0 && top.indexOf('px') !== -1) {
				offsetScroll += rect.height;
			}

			let item = {
				element: element,
				rect: rect,
				top: false,
				bottom: false
			};

			if (top.indexOf('px') !== -1) {
				item.top = Number(top.replace('px', ''));
				itemsTop.push(item);
			} else if (bottom.indexOf('px') !== -1) {
				item.bottom = Number(bottom.replace('px', ''));
				itemsBottom.unshift(item);
			}

		});

		if (itemsTop.length > 0) {

			itemsTop.sort(function(a, b) {
				return a.rect.top - b.rect.top;
			});

			items = itemsTop;

		}

		if (itemsBottom.length > 0) {

			itemsBottom.sort(function(a, b) {
				return b.rect.top - a.rect.top;
			});

			items = items.concat(itemsBottom);

		}

		items.forEach(function(item) {

			var element = item.element,
				rect = item.rect,
				isFixed = false,
				value = offsetTop + 'px';

			if (item.top !== false) {

				if (element.style.getPropertyValue('--offset-top') !== value) {
					element.style.setProperty('--offset-top', value);
					item.top = parseInt(window.getComputedStyle(element, null).getPropertyValue('top').replace('px', '')) || 0;
					rect = element.getBoundingClientRect();
				}

				if (Math.abs(item.top - rect.top) < 10) {
					offsetTop += rect.height;
					isFixed = window.scrollY > 0;
				}

			} else if (item.bottom !== false) {

				value = offsetBottom + 'px';

				if (element.style.getPropertyValue('--offset-bottom') !== value) {
					element.style.setProperty('--offset-bottom', value);
					item.bottom = parseInt(window.getComputedStyle(element, null).getPropertyValue('bottom').replace('px', '')) || 0;
					rect = element.getBoundingClientRect();
				}

				if (Math.abs(window.innerHeight - rect.height - rect.top - item.bottom) < 1) {
					offsetBottom += rect.height;
					isFixed = true;
				}

			}

			if ((!isFixed && element.classList.contains('is_fixed'))) {
				element.classList.remove('is_fixed');
			} else if (isFixed && !element.classList.contains('is_fixed')) {
				element.classList.add('is_fixed');
			}

		});

		updateProperty(document.body, '--offset-top', offsetTop + 'px');
		updateProperty(document.body, '--offset-bottom', offsetBottom + 'px');
		updateProperty(document.body, '--offset-scroll', offsetScroll + 'px');

		if (header) {

			var rect = header.getBoundingClientRect();

			if (rect.y > 0) {
				updateProperty(document.body, '--offset-header', rect.y + 'px');
			} else {
				updateProperty(document.body, '--offset-header', '0px');
			}

		}

	}, 50);

	function updateProperty(element, property, value) {
		if (element.style.getPropertyValue(property) !== value) {
			element.style.setProperty(property, value);
		}
	}

	window.addEventListener('scroll', handleScroll, {passive: true});
	window.addEventListener('scrollend', handleScroll, {passive: true});
	window.addEventListener('load', handleScroll, {passive: true});

	handleScroll();

});