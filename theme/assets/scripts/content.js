Twee.addModule('content', '.content.content_compact', function($, content) {

	updateContent(content);

	$(window).on('resize', function() {
		updateContent(content);
	});

	function updateContent(content) {

		var toggle = content.next('.toggle'),
			element = content.get(0),
			isExpanded = content.hasClass('expanded');

		// Temporarily remove the expanded state to accurately check for natural overflow
		if (isExpanded) {
			content.removeClass('expanded');
		}

		// Check if there's an overflow
		if (element.scrollHeight > element.clientHeight) {
			if (toggle.length < 1) {

				toggle = $('<button class="toggle" type="button">' + tw_template.lang_more + '</button>');

				content.after(toggle);

				toggle.on('click', function() {
					if (content.hasClass('expanded')) {
						content.removeClass('expanded').addClass('collapsed');
						toggle.text(tw_template.lang_more).removeClass('active');
					} else {
						content.addClass('expanded').removeClass('collapsed');
						toggle.text(tw_template.lang_less).addClass('active');
					}
				});
			}

			// Restore the correct classes and button state based on the previous state
			if (isExpanded) {
				content.addClass('expanded').removeClass('collapsed');
				toggle.text(tw_template.lang_less).addClass('active');
			} else {
				content.addClass('collapsed').removeClass('expanded');
				toggle.text(tw_template.lang_more).removeClass('active');
			}
		} else {
			// If no overflow (e.g., window widened), remove classes and the toggle button
			content.removeClass('collapsed expanded');

			if (toggle.length > 0) {
				toggle.remove();
			}
		}
	}

});