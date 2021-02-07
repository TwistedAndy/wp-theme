jQuery(function($) {

	$('[data-loader]').each(function() {

		var button = $(this),
			data = button.data('loader'),
			section = button.parents(data.wrapper),
			term = $('[name="term"]', section),
			search = $('[name="s"]', section),
			wrapper = section.find('.items');

		data.action = 'loader';
		data.noncer = template.nonce;

		wrapper.on('reset', function() {

			wrapper.children().remove();

			data.offset = 0;

			button.trigger('click');

		});

		term.on('change', function() {
			wrapper.trigger('reset');
		});

		search.on('input', debounce(function() {
			wrapper.trigger('reset');
		}, 500));

		button.on('click', function () {

			if (term.length > 0) {

				var term_id = term.val();

				data.terms = [];

				if (term_id) {
					data.terms = [term_id];
				}

			}

			if (search.length > 0) {
				data.search = search.val();
			}

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
							button.removeClass('is_hidden');
						} else {
							button.addClass('is_hidden');
						}

						section.trigger('init');

					} else {

						button.addClass('is_hidden');

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

	function debounce(fn, bufferInterval) {

		var timeout;

		return function() {
			clearTimeout(timeout);
			timeout = setTimeout(fn.apply.bind(fn, this, arguments), bufferInterval);
		};

	}

});