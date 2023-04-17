/* Image popup */

jQuery(function($) {

	if (typeof $.fn.fancybox !== 'function') {
		return;
	}

	$('section').each(function() {

		var section = $(this),
			gallery = 'a[href$=".png"], a[href$=".jpg"], a[href$=".jpeg"], a[href$=".gif"]',
			videos = 'a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"], a[href$="mp4"]';

		section.on('click', gallery, function(e) {

			var images = $(gallery, section);

			$.fancybox.open(images, {
				infobar: true
			}, images.index(this));

			e.preventDefault();

			return false;

		});

		section.on('click', videos, function(e) {
			$.fancybox.open($(this));
			e.preventDefault();
			return false;
		});

	});

});