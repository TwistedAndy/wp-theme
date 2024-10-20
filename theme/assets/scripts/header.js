Twee.addModule('header', '.header_box', function($, wrapper) {

	var submenus = $('.submenu', wrapper);

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

	wrapper.nextAll('section').first().attr('id', 'contents');

	wrapper.on('click', function(e) {
		if (e.target === this) {
			togglePanel(false);
		}
	});

	submenus.each(function() {

		var submenu = $(this),
			list = $('> ul', submenu),
			link = $('> a', submenu),
			back = $('> .back', list);

		link.click(function(e) {
			if (window.innerWidth <= 1024) {
				e.preventDefault();
			}
		});

		if (back.length === 0) {
			back = $('<li class="back">' + link.text() + '</li>');
			list.prepend(back);
		}

		submenu.click(function(e) {

			submenus.removeClass('is_active is_parent');

			if (window.innerWidth <= 1024) {
				submenu.addClass('is_active').parents('.submenu').addClass('is_active is_parent');
				submenus.find('.sub-menu').scrollTop(0);
			}

			e.stopPropagation();

		});

		back.click(function(e) {
			submenu.removeClass('is_active').parents('.submenu').removeClass('is_parent');
			e.stopPropagation();
		});

	});

	function togglePanel(toggleClass, lock) {

		var classes = ['is_search', 'is_cart', 'is_search', 'is_menu'].filter(function(value) {
			return value !== toggleClass;
		});

		wrapper.removeClass(classes.join(' '));

		if (!toggleClass || wrapper.hasClass(toggleClass)) {

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