Twee.addModule('sticky', 'html', function($) {

	let elements = $('.header_box.is_sticky'),
		header = $('.header_box').get(0);

	function handleScroll() {

		let topBar = 0,
			bottomBar = 0,
			itemsTop = [],
			itemsBottom = [];

		elements.each(function() {

			let element = this,
				styles = window.getComputedStyle(element, null),
				position = styles.getPropertyValue('position'),
				bottom = styles.getPropertyValue('bottom'),
				top = styles.getPropertyValue('top');

			if (position !== 'fixed' && position !== 'sticky') {
				return;
			}

			let data = {
				element: element,
				rect: this.getBoundingClientRect(),
				top: false,
				bottom: false
			};

			if (position === 'fixed') {

				top = parseInt(top.replace('px', ''));
				bottom = parseInt(bottom.replace('px', ''));

				if (top <= bottom) {
					data.top = top;
					itemsTop.push(data);
				} else {
					data.bottom = bottom;
					itemsBottom.unshift(data);
				}

			} else {

				if (top.indexOf('px') !== -1) {
					data.top = parseInt(top.replace('px', ''));
					itemsTop.push(data);
				} else if (bottom.indexOf('px') !== -1) {
					data.bottom = parseInt(bottom.replace('px', ''));
					itemsBottom.unshift(data);
				}

			}

		});

		if (itemsTop.length > 0) {
			itemsTop.sort(function(a, b) {
				return a.rect.top - b.rect.top;
			});
		}

		if (itemsBottom.length > 0) {
			itemsBottom.sort(function(a, b) {
				return b.rect.top - a.rect.top;
			});
		}

		itemsTop.concat(itemsBottom).forEach(function(item) {

			var element = item.element,
				rect = item.rect,
				isFixed = false,
				value = topBar + 'px';

			if (item.top !== false) {

				if (element.style.getPropertyValue('--offset-top') !== value) {
					element.style.setProperty('--offset-top', value);
					item.top = parseInt(window.getComputedStyle(element, null).getPropertyValue('top').replace('px', '')) || 0;
					rect = element.getBoundingClientRect();
				}

				if (Math.abs(item.top - rect.top) < 1) {
					topBar += rect.height;
					isFixed = window.scrollY > 0;
				}

			} else if (item.bottom !== false) {

				value = bottomBar + 'px';

				if (element.style.getPropertyValue('--offset-bottom') !== value) {
					element.style.setProperty('--offset-bottom', value);
					item.bottom = parseInt(window.getComputedStyle(element, null).getPropertyValue('bottom').replace('px', '')) || 0;
					rect = element.getBoundingClientRect();
				}

				if (Math.abs(window.innerHeight - rect.height - rect.top - item.bottom) < 1) {
					bottomBar += rect.height;
					isFixed = true;
				}

			}

			if ((!isFixed && element.classList.contains('is_fixed'))) {
				element.classList.remove('is_fixed');
			} else if (isFixed && !element.classList.contains('is_fixed')) {
				element.classList.add('is_fixed');
			}

		});

		updateProperty(document.body, '--offset-top', topBar + 'px');
		updateProperty(document.body, '--offset-bottom', bottomBar + 'px');

		if (header) {

			var rect = header.getBoundingClientRect();

			if (rect.y > 0) {
				updateProperty(document.body, '--offset-header', rect.y + 'px');
			} else {
				updateProperty(document.body, '--offset-header', '0px');
			}

		}

	}

	function updateProperty(element, property, value) {
		if (element.style.getPropertyValue(property) !== value) {
			element.style.setProperty(property, value);
		}
	}

	window.addEventListener('scroll', handleScroll);
	window.addEventListener('load', handleScroll);

	handleScroll();

});