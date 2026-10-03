Twee.addModule('sticky', 'html', function() {

	const global = window;

	// Elements stacked at the viewport edges, they get the --offset-top or --offset-bottom property and the is_stuck class
	const stickySelector = '.is_sticky, #wpadminbar';

	// Elements with the --offset-current property, they can be sticky or regular ones
	const currentSelector = '.header_box';

	// Sticky sidebars, which can be taller than the viewport and are scrolled with the page
	const sidebarSelector = '.wrapper_box [data-sidebar]';

	const page = {
		element: document.body,
		values: {}
	};

	let items = [],
		listeners = [],
		viewport = 0,
		lastScroll = -1,
		dirty = true,
		ticking = false;

	initStickyState();

	function initStickyState() {

		const observer = new ResizeObserver(requestMeasure);

		document.querySelectorAll(stickySelector + ', ' + currentSelector).forEach(function(element) {

			// The computed styles object is live, so it's enough to get it once
			items.push({
				element: element,
				styles: global.getComputedStyle(element),
				sticky: element.matches(stickySelector),
				current: element.matches(currentSelector),
				index: items.length,
				values: {}
			});

			observer.observe(element);

		});

		global.addEventListener('scroll', requestUpdate, { passive: true });

		['resize', 'load'].forEach((property) => {
			global.addEventListener(property, requestMeasure, { passive: true });
		});

		updateStickyState();

		global.StickySidebar = StickySidebar;

		document.querySelectorAll(sidebarSelector).forEach(function(element) {
			StickySidebar(element, {
				bottomSpacing: -50
			});
		});

	}

	function requestUpdate() {
		if (!ticking) {
			requestAnimationFrame(updateStickyState);
			ticking = true;
		}
	}

	function requestMeasure() {
		dirty = true;
		requestUpdate();
	}

	function updateStickyState() {

		ticking = false;

		let scroll = global.scrollY,
			offsets = {
				top: 0,
				bottom: 0
			},
			changed = false,
			measure = dirty,
			screen = 0,
			reserved = 0,
			item,
			rect,
			position,
			isStuck,
			i;

		if (!dirty && scroll === lastScroll) {
			return;
		}

		lastScroll = scroll;

		/**
		 * Split get and set operations to avoid forced reflows
		 */
		if (measure) {
			viewport = document.documentElement.clientHeight;
			dirty = false;
		}

		for (i = 0; i < items.length; i++) {

			item = items[i];
			rect = item.element.getBoundingClientRect();

			// Computed styles are changed on resize or with the offset only, so they are not read on scroll
			if (measure) {

				position = item.sticky ? item.styles.position : '';

				if (position === 'fixed') {
					item.type = 'fixed';
				} else if (position === 'sticky' && item.styles.top !== 'auto') {
					item.type = 'top';
				} else if (position === 'sticky' && item.styles.bottom !== 'auto') {
					item.type = 'bottom';
				} else {
					item.type = false;
				}

				item.base = parseFloat(item.styles[item.type]) || 0;

			}

			item.side = false;
			item.stuck = false;
			item.edge = Infinity;
			item.height = rect.height;
			item.top = rect.top;

			// Hidden elements have no height, so they are excluded
			if (rect.height === 0 || !item.type) {
				continue;
			}

			if (item.type === 'fixed') {
				// The computed top and bottom are always resolved for fixed elements, so the side is detected by the position
				item.side = rect.top + rect.height / 2 < viewport / 2 ? 'top' : 'bottom';
				item.stuck = true;
			} else if (item.type === 'top') {
				item.side = 'top';
				item.stuck = Math.abs(rect.top - item.base) < 1;
			} else {
				item.side = 'bottom';
				item.stuck = Math.abs(viewport - rect.bottom - item.base) < 1;
			}

			item.edge = item.side === 'top' ? rect.top : viewport - rect.bottom;

		}

		// Elements are stacked from the viewport edges to the center, the regular ones go last
		// The document order is used for elements at the same position, e.g. when the hidden one is shown again
		items.sort(function(a, b) {
			return a.edge - b.edge || a.index - b.index;
		});

		for (i = 0; i < items.length; i++) {

			item = items[i];
			isStuck = false;

			if (item.side) {

				changed = setOffset(item, item.side, offsets[item.side]) || changed;

				if (item.stuck) {
					offsets[item.side] += item.height;
					isStuck = item.side === 'bottom' || scroll > 0;
				}

				if (item.side === 'top') {

					// The space for all elements, which can be stuck at the top, so the anchor target is not covered after the scroll
					reserved += item.height;

					// The current distance from the viewport top, it's bigger than the top offset until the element is stuck
					if (item.current) {
						setOffset(item, 'current', Math.max(item.top, 0));
					}

				}

			} else if (item.current && item.height > 0) {
				// The regular element scrolls with the page, but its bottom edge never goes above the stuck elements
				setOffset(item, 'current', Math.max(item.top, offsets.top - item.height));
			}

			// The natural position is known only when the element is not stuck, so the value is kept in other cases
			if (item.current && !item.stuck && item.height > 0) {
				screen = Math.max(screen, Math.round(item.top + item.height + scroll));
			}

			if (item.marked !== isStuck) {
				item.element.classList.toggle('is_stuck', isStuck);
				item.marked = isStuck;
			}

		}

		let isMoved = setOffset(page, 'top', offsets.top);

		setOffset(page, 'bottom', offsets.bottom);
		setOffset(page, 'scroll', reserved);

		if (screen > 0) {
			setOffset(page, 'screen', screen);
		}

		// The changed offset moves the element, so its state should be checked again
		if (changed) {
			requestMeasure();
		}

		// Sidebars depend on the stuck elements, so they should be updated in the same frame
		if (isMoved) {
			listeners.forEach((listener) => listener());
		}

	}

	/**
	 * Values are cached to avoid touching the DOM when nothing is changed
	 */
	function setOffset(target, name, offset) {

		if (target.values[name] === offset) {
			return false;
		}

		target.values[name] = offset;
		target.element.style.setProperty('--offset-' + name, offset + 'px');

		return true;

	}

	function StickySidebar(sidebarElement, userOptions = {}) {

		const sidebar = typeof sidebarElement === 'string' ? document.querySelector(sidebarElement) : sidebarElement;

		if (!sidebar) {
			console.warn('Sticky element not specified');
			return;
		}

		const options = Object.assign({
			topSpacing: 0,
			bottomSpacing: 20,
			stickyClass: 'is-sticky'
		}, userOptions);

		// Internal State
		let currentTop = Infinity,
			lastScrollY = global.scrollY,
			isApplied = false,
			isDestroyed = false,
			isActive = false,
			isFitting = false,
			isMeasuring = false,
			baseTop = 0,
			appliedTop = null,
			sidebarHeight = 0,
			viewportHeight = 0;

		let resizeObserver = null;

		// Spacing functions can return different values on scroll, so they should be called every time
		const isDynamic = typeof options.topSpacing === 'function' || typeof options.bottomSpacing === 'function';

		// Private Methods
		const getTopSpacing = () => {
			const extraSpacing = typeof options.topSpacing === 'function' ? options.topSpacing(sidebar) : options.topSpacing;
			return baseTop + (parseInt(extraSpacing) || 0);
		};

		const getBottomSpacing = () => {
			return typeof options.bottomSpacing === 'function' ? parseInt(options.bottomSpacing(sidebar)) || 0 : parseInt(options.bottomSpacing) || 0;
		};

		const getScroll = () => {
			return Math.max(0, Math.min(document.documentElement.scrollHeight - global.innerHeight, global.scrollY));
		};

		const removeStyles = () => {
			if (isApplied) {
				sidebar.style.top = '';
				appliedTop = null;
				sidebar.classList.remove(options.stickyClass);
				isApplied = false;
			}
		};

		const updateSticky = (deltaY = 0) => {
			if (isDestroyed || !isActive) {
				return;
			}

			let topSpacing = getTopSpacing(),
				bottomSpacing = getBottomSpacing();

			isFitting = sidebarHeight + topSpacing + bottomSpacing <= viewportHeight;

			if (isFitting) {
				currentTop = topSpacing;
			} else {
				let minTop = viewportHeight - sidebarHeight - bottomSpacing,
					newTop = currentTop - deltaY;

				currentTop = Math.max(minTop, Math.min(topSpacing, newTop));
			}

			if (currentTop !== appliedTop) {
				sidebar.style.top = currentTop + 'px';
				appliedTop = currentTop;
			}
		};

		const handleScroll = () => {
			if (isDestroyed || !isActive) {
				return;
			}

			// The fitting sidebar has the constant top value, so there is nothing to do on scroll
			if (isFitting && !isDynamic) {
				return;
			}

			let currentScrollY = getScroll(),
				deltaY = currentScrollY - lastScrollY;

			lastScrollY = currentScrollY;

			updateSticky(deltaY);
		};

		const handleResize = () => {
			if (isDestroyed) {
				return;
			}

			// Offsets should be actual before reading the top value
			isMeasuring = true;
			dirty = true;
			updateStickyState();
			isMeasuring = false;

			// Sizes are changed on resize only, so they are not read on scroll
			sidebarHeight = sidebar.offsetHeight;
			viewportHeight = global.innerHeight;

			// The scroll position is not tracked for the fitting sidebar, so it should be synced before the state is changed
			if (isFitting) {
				lastScrollY = getScroll();
			}

			// Temporarily remove inline top style to accurately read CSS stylesheet values
			sidebar.style.top = '';
			appliedTop = null;

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

		// The top value depends on the stuck elements, so it should be read again when they are changed
		const handleOffset = () => {
			if (!isMeasuring) {
				handleResize();
			}
		};

		const destroy = () => {
			isDestroyed = true;

			listeners = listeners.filter((listener) => listener !== handleOffset);

			global.removeEventListener('scroll', handleScroll);
			global.removeEventListener('resize', handleResize);

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

			global.addEventListener('scroll', handleScroll, { passive: true });
			global.addEventListener('resize', handleResize, { passive: true });

			listeners.push(handleOffset);

			handleResize();
		};

		init();

		// Public API
		return {
			destroy,
			updateSticky: (deltaY = 0) => {
				sidebarHeight = sidebar.offsetHeight;
				updateSticky(deltaY);
			}
		};
	}

});