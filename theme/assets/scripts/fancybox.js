/* Image popup */

jQuery(function($) {

	if (typeof $.fn.fancybox !== 'undefined') {

		$('section').each(function() {

			var section = $(this);

			section.on('init', function() {

				var gallery = $('a[href*=".png"], a[href*=".jpg"], a[href*=".jpeg"], a[href*=".gif"]', this);

				var videos = $('a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"]', this);

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