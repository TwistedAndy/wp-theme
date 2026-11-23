Twee.addModule('content', '.content.content_compact', function($, content) {

	updateContent(content);

	$(window).on('resize', function() {
		updateContent(content);
	});

	function updateContent(content) {

		var toggle = content.next('.toggle'),
			element = content.get(0),
			isExpanded = content.hasClass('content_expanded');

		// Temporarily remove the expanded state to accurately check for natural overflow
		if (isExpanded) {
			content.removeClass('content_expanded');
		}

		// Check if there's an overflow
		if (element.scrollHeight > element.clientHeight) {
			if (toggle.length < 1) {

				toggle = $('<button class="toggle" type="button">' + tw_template.lang_more + '</button>');

				content.after(toggle);

				toggle.on('click', function() {
					if (content.hasClass('content_expanded')) {
						content.removeClass('content_expanded').addClass('content_collapsed');
						toggle.text(tw_template.lang_more).removeClass('active');
					} else {
						content.addClass('content_expanded').removeClass('content_collapsed');
						toggle.text(tw_template.lang_less).addClass('active');
					}
				});
			}

			// Restore the correct classes and button state based on the previous state
			if (isExpanded) {
				content.addClass('content_expanded').removeClass('content_collapsed');
				toggle.text(tw_template.lang_less).addClass('active');
			} else {
				content.addClass('content_collapsed').removeClass('content_expanded');
				toggle.text(tw_template.lang_more).removeClass('active');
			}
		} else {
			// If no overflow (e.g., window widened), remove classes and the toggle button
			content.removeClass('content_collapsed content_expanded');

			if (toggle.length > 0) {
				toggle.remove();
			}
		}
	}

});