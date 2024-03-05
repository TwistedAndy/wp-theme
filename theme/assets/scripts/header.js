jQuery(document).on('tw_init', '.header_box', function(e, $) {

	if (runOnce(this, 'header')) {
		return;
	}

	var wrapper = $(this),
		submenus = $('.submenu', wrapper),
		isFixed = false;

	$(window).on('beforeunload pagehide', function() {
		togglePanel(false);
	});

	wrapper.on('click', '.menu_btn', function(e) {
		togglePanel('is_menu', true);
		e.preventDefault();
	});

	wrapper.on('click', '.search_btn', function(e) {
		togglePanel('is_search', true);
		e.preventDefault();
	});

	submenus.click(function(e) {

		var submenu = $(this);

		if (window.innerWidth <= 1024) {

			submenu.siblings('.submenu').removeClass('is_expanded').children('ul').slideUp();

			submenu.toggleClass('is_expanded').children('ul').slideToggle(400, function() {
				if (this.style.display === 'none') {
					this.style.removeProperty('display');
				}
			});

		} else {

			submenus.removeClass('is_expanded');

			submenus.children('ul').each(function() {
				if (this.style.display === 'none') {
					this.style.removeProperty('display');
				}
			});

		}

	});

	window.addEventListener('scroll', handleScroll);

	handleScroll();

	function handleScroll() {

		var offset = wrapper.offset().top,
			position = wrapper.position().top,
			offsetFull = offset;

		if (document.body.classList.contains('admin-bar')) {
			if (window.innerWidth <= 782 && window.innerWidth >= 600) {
				offsetFull -= 46;
			} else if (window.innerWidth > 782) {
				offsetFull -= 32;
			}
		}

		if (window.scrollY > offsetFull) {

			if (!isFixed) {
				wrapper.addClass('is_fixed');
				document.body.style.setProperty('--header-offset', '0px');
			}

			isFixed = true;

		} else {

			if (isFixed) {
				wrapper.removeClass('is_fixed');
			}

			document.body.style.setProperty('--header-offset', (position - window.scrollY) + 'px');

			isFixed = false;

		}

		document.body.style.setProperty('--header-shift', offset + 'px');

	}

	function togglePanel(toggleClass, lock) {

		var classes = ['is_search', 'is_cart', 'is_search'].filter(function(value) {
			return value !== toggleClass;
		});

		wrapper.removeClass(classes.join(' '));

		if (wrapper.hasClass(toggleClass) || !toggleClass) {

			wrapper.removeClass(toggleClass);

			if (toggleClass === 'is_search') {
				$('[name="s"]', wrapper).blur();
			}

			unlockScroll();

		} else {

			wrapper.addClass(toggleClass);

			if (toggleClass === 'is_search') {
				$('[name="s"]', wrapper).focus();
			}

			if (lock) {
				lockScroll();
			}

		}

	}

});