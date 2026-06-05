Twee.addModule('sticky', 'html', function($) {

	const global = window;

	let screenOffset = 0,
		updateScreenOffset = true,
		isAdmin = document.body.classList.contains('admin-bar'),
		elements = document.querySelectorAll('.header_box.is_sticky'),
		header = $('.header_box').get(0),
		ticking = false;

	initStickyState();

	function initStickyState() {

		global.StickySidebar = StickySidebar;

		const handleScroll = Twee.throttle(function() {
			if (!ticking) {
				requestAnimationFrame(updateStickyState);
				ticking = true;
			}
		}, 16);

		['resize', 'scroll', 'scrollend', 'orientationchange', 'load'].forEach((property) => {
			global.addEventListener(property, handleScroll, { passive: true });
		});

		updateStickyState();

		$('.wrapper_box [data-sidebar]').each(function() {
			StickySidebar(this, {
				bottomSpacing: -50
			});
		});

	}

	function updateStickyState() {

		ticking = false;

		let offsetHeader = 0,
			offsetScroll = 0,
			offsetTop = 0,
			offsetBottom = 0,
			items = [],
			itemsTop = [],
			itemsBottom = [];

		if (global.scrollY === 0) {
			updateScreenOffset = true;
		}

		if (updateScreenOffset) {
			screenOffset = 0;
		}

		if (isAdmin) {

			if (global.innerWidth <= 782 && global.innerWidth >= 600) {
				offsetTop += 46;
			} else if (global.innerWidth > 782) {
				offsetTop += 32;
			}

			offsetScroll = offsetTop;

			if (updateScreenOffset) {
				screenOffset = offsetTop;
			}

		}

		elements.forEach(function(element) {

			let styles = global.getComputedStyle(element, null),
				position = styles.getPropertyValue('position');

			if (position !== 'fixed' && position !== 'sticky') {
				return;
			}

			let bottom = styles.getPropertyValue('bottom'),
				top = styles.getPropertyValue('top'),
				rect = element.getBoundingClientRect();

			if (rect.height > 0 && top.indexOf('px') !== -1) {
				if (updateScreenOffset && element !== header && position === 'sticky') {
					screenOffset += rect.height;
				}

				if (position === 'sticky' || (position === 'fixed' && bottom.indexOf('px') !== -1)) {
					offsetScroll += rect.height;
				}
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

				value = Math.max(rect.y, -rect.height, offsetTop) + 'px';

				if (element.style.getPropertyValue('--offset-top') !== value) {
					propertyChanges.push({
						'element': element,
						'property': '--offset-top',
						'value': value
					});
					item.top = parseInt(global.getComputedStyle(element, null).getPropertyValue('top').replace('px', '')) || 0;
					rect = element.getBoundingClientRect();
					item.rect = rect;
				}

				if (Math.abs(item.top - rect.top) < 10) {
					offsetTop += rect.height;
					isFixed = global.scrollY > 0;
				}

			} else if (item.bottom !== false) {

				value = offsetBottom + 'px';

				if (element.style.getPropertyValue('--offset-bottom') !== value) {
					propertyChanges.push({
						'element': element,
						'property': '--offset-bottom',
						'value': value
					});
					item.bottom = parseInt(global.getComputedStyle(element, null).getPropertyValue('bottom').replace('px', '')) || 0;
					rect = element.getBoundingClientRect();
					item.rect = rect;
				}

				if (Math.abs(global.innerHeight - rect.height - rect.top - item.bottom) < 1) {
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

				if (global.scrollY === 0) {
					screenOffset += headerRect.y;
					screenOffset += headerRect.height;
					updateScreenOffset = false;
				} else if (updateScreenOffset) {
					header.style.setProperty('position', 'static', 'important');

					let realOffset = header.getBoundingClientRect().y + global.scrollY;

					header.style.removeProperty('position');

					screenOffset += realOffset;
					screenOffset += headerRect.height;
					updateScreenOffset = false;
				}

			}

		}

		/**
		 * Split property get and set operations to avoid forced reflows
		 */
		let properties = ['--offset-top', '--offset-bottom', '--offset-scroll', '--offset-header', '--offset-screen'];

		let values = [offsetTop + 'px', offsetBottom + 'px', offsetScroll + 'px', offsetHeader + 'px', screenOffset + 'px'];

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

	function StickySidebar(sidebarElement, userOptions = {}) {

		const sidebar = typeof sidebarElement === 'string' ? document.querySelector(sidebarElement) : sidebarElement;

		if (!sidebar) {
			console.warn('Sticky element not specified');
		}

		const options = Object.assign({
			topSpacing: 0,
			bottomSpacing: 20,
			stickyClass: 'is-sticky'
		}, userOptions);

		// Internal State
		let currentTop = 0,
			lastScrollY = global.scrollY,
			isApplied = false,
			isDestroyed = false,
			isActive = false,
			baseTop = 0;

		let resizeObserver = null;

		// Private Methods
		const getTopSpacing = () => {
			const extraSpacing = typeof options.topSpacing === 'function' ? options.topSpacing(sidebar) : options.topSpacing;
			return baseTop + (parseInt(extraSpacing) || 0);
		};

		const getBottomSpacing = () => {
			return typeof options.bottomSpacing === 'function' ? parseInt(options.bottomSpacing(sidebar)) || 0 : parseInt(options.bottomSpacing) || 0;
		};

		const removeStyles = () => {
			if (isApplied) {
				sidebar.style.top = '';
				sidebar.classList.remove(options.stickyClass);
				isApplied = false;
			}
		};

		const updateSticky = (deltaY = 0) => {
			if (isDestroyed || !isActive) {
				return;
			}

			let sidebarHeight = sidebar.offsetHeight,
				viewportHeight = global.innerHeight,
				topSpacing = getTopSpacing(),
				bottomSpacing = getBottomSpacing();

			if (sidebarHeight + topSpacing + bottomSpacing <= viewportHeight) {
				currentTop = topSpacing;
			} else {
				let minTop = viewportHeight - sidebarHeight - bottomSpacing,
					newTop = currentTop - deltaY;

				currentTop = Math.max(minTop, Math.min(topSpacing, newTop));
			}

			sidebar.style.top = currentTop + 'px';
		};

		const handleScroll = () => {
			if (isDestroyed || !isActive) {
				return;
			}

			let maxScroll = document.documentElement.scrollHeight - global.innerHeight,
				currentScrollY = Math.max(0, Math.min(maxScroll, global.scrollY)),
				deltaY = currentScrollY - lastScrollY;

			lastScrollY = currentScrollY;

			updateSticky(deltaY);
		};

		const handleResize = () => {
			if (isDestroyed) {
				return;
			}

			updateStickyState();

			// Temporarily remove inline top style to accurately read CSS stylesheet values
			sidebar.style.top = '';

			let styles = global.getComputedStyle(sidebar, null),
				top = styles.getPropertyValue('top');

			if (top.indexOf('px') !== -1) {
				baseTop = Number(top.replace('px', ''));
			} else {
				baseTop = 0;
			}

			// The sidebar is active if it has the computed style with position: sticky
			isActive = (styles.getPropertyValue('position') === 'sticky');

			if (!isActive) {
				removeStyles();
				return;
			}

			if (!isApplied) {
				sidebar.classList.add(options.stickyClass);
				isApplied = true;
			}

			updateSticky(0);
		};

		const destroy = () => {
			isDestroyed = true;

			['scroll', 'scrollend'].forEach((property) => {
				global.removeEventListener(property, handleScroll);
			});

			['resize', 'orientationchange'].forEach((property) => {
				global.removeEventListener(property, handleResize);
			});

			if (resizeObserver) {
				resizeObserver.disconnect();
			}

			removeStyles();
		};

		const init = () => {
			if (typeof ResizeObserver !== 'undefined') {
				resizeObserver = new ResizeObserver(handleResize);
				resizeObserver.observe(sidebar);

				if (sidebar.parentElement) {
					resizeObserver.observe(sidebar.parentElement);
				}
			}

			['scroll', 'scrollend'].forEach((property) => {
				global.addEventListener(property, handleScroll, { passive: true });
			});

			['resize', 'orientationchange'].forEach((property) => {
				global.addEventListener(property, handleResize, { passive: true });
			});

			handleResize();

			currentTop = getTopSpacing();
		};

		init();

		// Public API
		return {
			destroy,
			updateSticky
		};
	}

});