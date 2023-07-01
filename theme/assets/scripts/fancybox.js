/* Image popup */

jQuery(function($) {

	if (typeof Fancybox !== 'function') {
		return;
	}

	$('section').each(function() {

		Fancybox.bind(this, 'a[href$=".png"], a[href$=".jpg"], a[href$=".jpeg"], a[href$=".gif"]', {
			groupAll: true
		});

		Fancybox.bind(this, 'a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"], a[href$="mp4"]', {});

	});

});