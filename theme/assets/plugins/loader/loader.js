jQuery(function($) {

	$('[data-loader]').each(function() {

		var button = $(this),
			data = button.data('loader'),
			section = button.parents(data.wrapper),
			terms = $('[data-term]', section),
			search = $('[name="s"]', section),
			wrapper = section.find('.items');

		var refreshItems = debouncer(function() {
			section.trigger('reset');
		}, 1000);

		data.action = 'loader';
		data.noncer = template.nonce;

		section.on('reset', function() {
			data = button.data('loader');
			data.offset = 0;
			button.data('loader', data);
			wrapper.removeAttr('style').css('height', wrapper.height());
			button.trigger('click');
		});

		terms.on('click', function(e) {

			$(this).addClass('active').siblings().removeClass('active');

			refreshItems();

			e.preventDefault();

			return false;

		});

		search.on('input', refreshItems);

		button.on('click', function() {

			data = button.data('loader');

			var slider = wrapper.data('slider');

			data.terms = [];

			terms.filter('.active').each(function() {
				var term = $(this).data('term');
				if (term > 0) {
					data.terms.push(term);
				}
			});

			if (search.length > 0) {
				data.search = search.val();
			} else {
				data.search = '';
			}

			$.ajax(template.ajaxurl, {
				type: 'post',
				dataType: 'json',
				data: data,
				beforeSend: function() {
					button.addClass('is_loading');
					wrapper.addClass('is_loading');
				}
			}).always(function() {

				button.removeClass('is_loading');
				wrapper.removeClass('is_loading');
				section.trigger('loaded');

			}).done(function(response) {

				var heightOld = wrapper.height(),
					heightNew = 0;

				wrapper.removeAttr('style');

				if (slider) {
					slider.destroy();
				}

				if (data.offset === 0) {
					wrapper.children().remove();
				}

				if (response['result']) {

					var posts = $(response['result']);

					wrapper.append(posts);
					wrapper.removeAttr('style');
					heightNew = wrapper.height();
					wrapper.css('height', heightOld);

					button.addClass('is_hidden');

					if (data.number > 0) {

						data.offset = data.offset + data.number;

						button.data('loader', data);

						if (response['more']) {
							button.removeClass('is_hidden');
						}

					}

					section.trigger('init');

					wrapper.animate({height: heightNew}, 400, function() {
						wrapper.removeAttr('style');
					});

				} else {

					button.addClass('is_hidden');
					wrapper.removeAttr('style');

				}

			});

		});

	});

});