/* Image popup */

jQuery(function($) {

	if (typeof $.fn.fancybox !== 'undefined') {

		$('section').each(function() {

			let section = $(this);

			section.on('init', function() {

				let gallery = $('a[href$=".png"], a[href$=".jpg"], a[href$=".jpeg"], a[href$=".gif"]', this);

				let videos = $('a[href*="youtube.com"], a[href*="vimeo.com"]', this);

				gallery.off('click').on('click', function(e) {

					$.fancybox.open(gallery, {
						infobar: true
					}, gallery.index(this));

					e.preventDefault();

					return false;

				});

				videos.fancybox();

			});

			section.trigger('init');

		});

	}

});