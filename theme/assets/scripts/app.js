const Twee = {

	modules: {},

	/**
	 * Initialize app
	 */
	initApp: function() {

		window.addEventListener('load', Twee.initStyles);
		window.addEventListener('resize', Twee.initStyles);
		document.addEventListener('DOMContentLoaded', Twee.initStyles);

		window.addEventListener('load', Twee.initModules);
		window.addEventListener('rocket-load', Twee.initModules);
		document.addEventListener('rocket-DOMContentLoaded', Twee.initModules);
		document.addEventListener('DOMContentLoaded', Twee.initModules);

	},

	/**
	 * Initialize base styles
	 */
	initStyles: function() {

		let scrollbarWidth = parseInt(window.innerWidth - document.documentElement.clientWidth);

		if (isNaN(scrollbarWidth) || scrollbarWidth < 0) {
			scrollbarWidth = 0;
		}

		document.body.style.setProperty('--width-scrollbar', scrollbarWidth + 'px');

	},

	/**
	 * Initialize all modules
	 */
	initModules: function() {

		let selectors = [];

		Object.getOwnPropertyNames(Twee.modules).forEach(function(name) {

			let module = Twee.modules[name];

			if (!module.attached) {
				jQuery(document).on('tw_init', module.selector, module.callback);
				module.attached = true;
				selectors = selectors.concat(module.selector.split(','));
			}

		});

		if (selectors.length > 0) {

			selectors = selectors.map(function(selector) {
				return selector.toString().trim();
			});

			selectors = selectors.filter(function(item, i, array) {
				return array.indexOf(item) === i;
			});

			Twee.initModule(selectors.join(', '));

		}

	},

	/**
	 * Initialize a module
	 *
	 * @param selector
	 */
	initModule: function(selector) {
		jQuery(selector).each(function() {
			jQuery(this).trigger('tw_init', [jQuery, jQuery(this)]);
		});
	},

	/**
	 * Add a module to the registry
	 *
	 * The system will run a callback when the page is loaded and all the dependencies are presented.
	 * A callback is triggered once per element, but it can be changed with the multiple flag.
	 *
	 * @param {string}   key        A unique module ID
	 * @param {string}   selector   Root element selector
	 * @param {function} callback   A function called on page initialization
	 * @param {Array}    deps       An array with global dependencies
	 * @param {boolean}  multiple   Allow running a callback more than once on an element
	 * @param {int}      timeout    Allow running a callback again when the timeout is reached
	 */
	addModule: function(key, selector, callback, deps = [], multiple = false, timeout = 0) {

		if (selector) {
			selector = selector.toString();
		} else {
			selector = 'html';
		}

		if (typeof this.modules[key] === 'undefined') {

			this.modules[key] = {
				attached: false,
				selector: selector,
				callback: function(e) {

					let status = true,
						target = e.currentTarget;

					if (deps && deps.length > 0) {
						deps.forEach(function(dep) {
							if (typeof window[dep] === 'undefined') {
								status = false;
							}
						});
					}

					if (!status) {
						return;
					}

					if (multiple || Twee.runOnce(target, key, timeout)) {
						callback.call(target, jQuery, jQuery(target), e);
					}

				}
			};

		} else {

			console.warn('Module ' + key + ' is already added');

		}

	},

	/**
	 * Run a code only once per element
	 *
	 * @param {HTMLElement} element
	 * @param {string}      slug
	 * @param {int}         timeout
	 *
	 * @returns {boolean}
	 */
	runOnce: function(element, slug, timeout = 0) {

		slug = slug || 'element';

		let key = 'tw_' + slug + '_loaded';

		if (timeout > 0) {
			setTimeout(function() {
				element[key] = false;
			}, timeout);
		}

		if (element[key]) {
			return false;
		} else {
			element[key] = true;
			return true;
		}

	},

	/**
	 * Run a callback only once after specified delay
	 *
	 * @param {function} callback
	 * @param {int}      delay
	 *
	 * @returns {function}
	 */
	runLater: function(callback, delay) {

		let timeout;

		return function() {
			clearTimeout(timeout);
			timeout = setTimeout(callback.apply.bind(callback, this, arguments), delay);
		};

	},

	/**
	 * Smooth scroll to an element
	 *
	 * @param {HTMLElement|jQuery}  element
	 * @param {int}                 speed
	 * @param {int}                 offset
	 */
	smoothScrollTo: function(element, speed = 1000, offset = 0) {

		let wrapper = jQuery('html, body');

		element = jQuery(element).first();

		if (element.length === 0) {
			return;
		}

		let top = document.body.style.getPropertyValue('--offset-scroll');

		if (top.indexOf('px') !== -1) {
			top = Number(top.replace('px', ''));
		} else {
			top = 0;
		}

		let position = element.offset().top - top - offset;

		if (element.attr('id')) {
			let scroll = wrapper.scrollTop();
			window.location.hash = element.attr('id');
			wrapper.scrollTop(scroll);
		}

		wrapper.stop().animate({
			'scrollTop': position
		}, speed);

	},

	/**
	 * Lock the screen scroll
	 */
	lockScroll: function() {
		document.body.classList.add('is_locked');
	},

	/**
	 * Unlock the screen scroll
	 */
	unlockScroll: function() {
		document.body.classList.remove('is_locked');
	},

	/**
	 * Set a cookie value
	 *
	 * @param {string}  name
	 * @param {string}  value
	 * @param {int}     expire
	 */
	setCookie: function(name, value, expire = 365) {

		let date = new Date();

		date.setTime(date.getTime() + (expire * 24 * 60 * 60 * 1000));

		document.cookie = name.toString() + '=' + value.toString() + ';expires=' + date.toUTCString() + ';path=/';

	},

	/**
	 * Get a cookie value
	 *
	 * @param {string} name
	 *
	 * @returns {string}
	 */
	getCookie: function(name) {

		name = name + '=';

		let decodedCookie = decodeURIComponent(document.cookie),
			parts = decodedCookie.split(';');

		for (let i = 0; i < parts.length; i++) {

			let part = parts[i];

			while (part.charAt(0) === ' ') {
				part = part.substring(1);
			}

			if (part.indexOf(name) === 0) {
				return part.substring(name.length, part.length);
			}

		}

		return '';

	},

};

Twee.initApp();