jQuery(document).on('tw_init', '[class*="_box"]', function(e, $) {

	if (typeof Fancybox !== 'function') {
		return;
	}

	Fancybox.bind(this, 'a[href$=".png"], a[href$=".jpg"], a[href$=".jpeg"], a[href$=".gif"]', {
		groupAll: true
	});

	Fancybox.bind(this, 'a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"], a[href$="mp4"]', {});

});