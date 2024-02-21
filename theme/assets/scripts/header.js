jQuery(document).on('tw_init', '.header_box', function(e, $) {

	if (runOnce(this, 'header')) {
		return;
	}

	var wrapper = $(this),
		submenus = $('.submenu', wrapper),
		isFixed = false;

	wrapper.on('click', '.menu_btn', function() {
		if (wrapper.hasClass('is_menu')) {
			wrapper.removeClass('is_menu');
			unlockScroll();
		} else {
			wrapper.addClass('is_menu');
			lockScroll();
		}
	});

	$(window).on('beforeunload pagehide', function() {
		wrapper.removeClass('is_menu');
		unlockScroll();
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
				document.documentElement.style.setProperty('--header-offset', '0px');
			}

			isFixed = true;

		} else {

			if (isFixed) {
				wrapper.removeClass('is_fixed');
			}

			document.documentElement.style.setProperty('--header-offset', (offset - window.scrollY) + 'px');

			isFixed = false;

		}

	}

});