Twee.addModule('sticky', 'html', function($) {

	let isAdmin = document.body.classList.contains('admin-bar'),
		elements = document.querySelectorAll('.header_box.is_sticky'),
		header = $('.header_box').get(0),
		updateScreenOffset = true,
		ticking = false;

	const handleScroll = Twee.throttle(function() {
		if (!ticking) {
			requestAnimationFrame(updateStickyState);
			ticking = true;
		}
	}, 16);

	let offsetScreen = 0;

	function updateStickyState() {

		ticking = false;

		let offsetHeader = 0,
			offsetScroll = 0,
			offsetTop = 0,
			offsetBottom = 0,
			items = [],
			itemsTop = [],
			itemsBottom = [];

		if (window.scrollY === 0) {
			updateScreenOffset = true;
		}

		if (updateScreenOffset) {
			offsetScreen = 0;
		}

		if (isAdmin) {

			if (window.innerWidth <= 782 && window.innerWidth >= 600) {
				offsetTop += 46;
			} else if (window.innerWidth > 782) {
				offsetTop += 32;
			}

			offsetScroll = offsetTop;

			if (updateScreenOffset) {
				offsetScreen = offsetTop;
			}

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
				if (updateScreenOffset && element !== header && position === 'sticky') {
					offsetScreen += rect.height;
				}

				offsetScroll += rect.height;
			}

			let item = {
				element: element,
				rect: rect,
				top: false,
				bottom: false,
				position: position
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

		let headerRect = false,
			propertyChanges = [],
			classChanges = [];

		items.forEach(function(item) {

			let element = item.element,
				rect = item.rect,
				isFixed = false,
				value = offsetTop + 'px';

			if (item.top !== false) {

				if (element.style.getPropertyValue('--offset-top') !== value) {
					propertyChanges.push({
						'element': element,
						'property': '--offset-top',
						'value': value
					});
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
					propertyChanges.push({
						'element': element,
						'property': '--offset-bottom',
						'value': value
					});
					item.bottom = parseInt(window.getComputedStyle(element, null).getPropertyValue('bottom').replace('px', '')) || 0;
					rect = element.getBoundingClientRect();
				}

				if (Math.abs(window.innerHeight - rect.height - rect.top - item.bottom) < 1) {
					offsetBottom += rect.height;
					isFixed = true;
				} else if (item.position === 'sticky') {
					offsetTop += rect.height;
				}

			}

			if ((!isFixed && element.classList.contains('is_fixed'))) {
				classChanges.push({
					'element': element,
					'class': 'is_fixed',
					'status': false
				});
			} else if (isFixed && !element.classList.contains('is_fixed')) {
				classChanges.push({
					'element': element,
					'class': 'is_fixed',
					'status': true
				});
			}

			if (element === header) {
				headerRect = rect;
			}

		});

		if (header) {

			if (headerRect === false) {
				headerRect = header.getBoundingClientRect();
			}

			if (headerRect.y > 0) {
				offsetHeader = headerRect.y;
			}

			if (headerRect.height > 0) {

				if (window.scrollY === 0) {
					offsetScreen += headerRect.y;
					offsetScreen += headerRect.height;
					updateScreenOffset = false;
				} else if (updateScreenOffset) {
					header.style.setProperty('position', 'static', 'important');

					let realOffset = header.getBoundingClientRect().y + window.scrollY;

					header.style.removeProperty('position');

					offsetScreen += realOffset;
					offsetScreen += headerRect.height;
					updateScreenOffset = false;
				}

			}

		}

		/**
		 * Split property get and set operations to avoid forced reflows
		 */
		let properties = [
			'--offset-top',
			'--offset-bottom',
			'--offset-scroll',
			'--offset-header',
			'--offset-screen'
		];

		let values = [
			offsetTop + 'px',
			offsetBottom + 'px',
			offsetScroll + 'px',
			offsetHeader + 'px',
			offsetScreen + 'px'
		];

		properties.forEach(function(property, index) {
			if (document.body.style.getPropertyValue(property) !== values[index]) {
				propertyChanges.push({
					'element': document.body,
					'property': property,
					'value': values[index]
				});
			}
		});

		if (propertyChanges.length > 0) {
			propertyChanges.forEach(function(change) {
				change.element.style.setProperty(change.property, change.value);
			});
		}

		if (classChanges.length > 0) {
			classChanges.forEach(function(change) {
				if (change.status) {
					change.element.classList.add(change.class);
				} else {
					change.element.classList.remove(change.class);
				}
			});
		}

	}

	window.addEventListener('scroll', handleScroll, {passive: true});
	window.addEventListener('scrollend', handleScroll, {passive: true});
	window.addEventListener('resize', handleScroll, {passive: true});
	window.addEventListener('load', handleScroll, {passive: true});

});