Twee.addModule('content', 'html', function($, wrapper) {

	$('.content.is_compact').each(function() {

		var content = $(this);

		updateContent(content);

		$(window).on('resize', function() {
			updateContent(content);
		});

	});

	function updateContent(content) {

		var toggle = content.next('.toggle'),
			element = content.get(0),
			isExpanded = content.hasClass('is_expanded');

		// Temporarily remove the expanded state to accurately check for natural overflow
		if (isExpanded) {
			content.removeClass('is_expanded');
		}

		// Check if there's an overflow
		if (element.scrollHeight > element.clientHeight) {
			if (toggle.length < 1) {

				toggle = $('<button class="toggle" type="button">' + tw_template.lang_more + '</button>');

				content.after(toggle);

				toggle.on('click', function() {
					if (content.hasClass('is_expanded')) {
						content.removeClass('is_expanded').addClass('is_collapsed');
						toggle.text(tw_template.lang_more).removeClass('is_active');
					} else {
						content.addClass('is_expanded').removeClass('is_collapsed');
						toggle.text(tw_template.lang_less).addClass('is_active');
					}
				});
			}

			// Restore the correct classes and button state based on the previous state
			if (isExpanded) {
				content.addClass('is_expanded').removeClass('is_collapsed');
				toggle.text(tw_template.lang_less).addClass('is_active');
			} else {
				content.addClass('is_collapsed').removeClass('is_expanded');
				toggle.text(tw_template.lang_more).removeClass('is_active');
			}
		} else {
			// If no overflow (e.g., window widened), remove classes and the toggle button
			content.removeClass('is_collapsed is_expanded');

			if (toggle.length > 0) {
				toggle.remove();
			}
		}
	}

});