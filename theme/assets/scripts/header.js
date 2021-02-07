jQuery(function($) {

	$('.header_box').each(function() {

		var wrapper = $(this), submenus = $('.submenu', wrapper);

		$('.menu_btn', wrapper).click(function() {
			wrapper.toggleClass('is_menu');
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

			if (e.target === this) {
				e.stopPropagation();
				return false;
			}

		});

		window.addEventListener('scroll', handleScroll);

		handleScroll();

		function handleScroll() {

			var offset = wrapper.offset().top + 40;

			if (window.pageYOffset > offset) {
				wrapper.addClass('is_compact');
			} else {
				wrapper.removeClass('is_compact');
			}

		}

	});

});