Twee.addModule('fancybox', 'section', function() {

	Fancybox.bind(this, 'a[href*=".png"], a[href*=".jpg"], a[href*=".jpeg"], a[href*=".gif"], a[href*=".webp"]', {
		groupAll: true
	});

	Fancybox.bind(this, 'a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"], a[href*=".mp4"]', {});

}, ['Fancybox']);