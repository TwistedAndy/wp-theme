jQuery(function($) {

	$('[data-loader]').each(function() {

		var button = $(this),
			data = button.data('loader'),
			section = button.parents(data.wrapper),
			wrapper = section.find('.items');

		wrapper.on('reset', function() {

			data = button.data('loader');

			wrapper.children().remove();

			data.offset = 0;

			button.trigger('click');

		});

		button.click(function() {

			data.action = 'load_posts';

			data.noncer = template.nonce;

			$.ajax({
				url: template.ajaxurl,
				type: 'post',
				dataType: 'json',
				data: data,
				success: function(response) {

					if (response['result']) {

						var posts = $(response['result']);

						wrapper.append(posts);

						data.offset = data.offset + data.number;

						if (response['more']) {
							button.removeClass('hidden');
						} else {
							button.addClass('hidden');
						}

						section.trigger('init');

					} else {

						button.addClass('hidden');

					}

				},
				beforeSend: function() {
					button.addClass('is_loading');
				},
				complete: function() {
					button.removeClass('is_loading');
				}

			});

		});

	});

});