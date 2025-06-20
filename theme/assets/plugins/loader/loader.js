Twee.addModule('loader', 'html', function($, container) {

	$('[data-loader]', container).each(function() {

		var button = $(this),
			data = button.data('loader'),
			section = button.closest(data.wrapper);

		if (section.closest('.wrapper_box').length > 0) {
			section = section.closest('.wrapper_box');
		}

		var terms = $('[data-term]', section),
			search = $('[name="s"]', section),
			wrapper = section.find('.items'),
			animate = !wrapper.hasClass('carousel');

		var refreshItems = Twee.debounce(function() {
			section.trigger('reset');
		}, 1000);

		data.action = 'loader';
		data.noncer = tw_template.nonce;

		section.on('reset', function() {

			data = button.data('loader');
			data.offset = 0;

			button.data('loader', data);
			button.trigger('click');

			if (animate) {
				wrapper.removeAttr('style').css('height', wrapper.height());
			}

		});

		terms.on('click', function(e) {

			var term = $(this),
				list = term.closest('.woocommerce-widget-layered-nav-list');

			if (list.length > 0) {
				term.toggleClass('active').parent().toggleClass('chosen');
			} else {
				term.addClass('active').siblings().removeClass('active');
			}

			refreshItems();

			e.preventDefault();

			return false;

		});

		search.on('input', refreshItems);

		button.on('click', function() {

			var carousel = wrapper.data('carousel');

			data = button.data('loader');

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

			$.ajax(tw_template.ajaxurl, {
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

				if (animate) {
					wrapper.removeAttr('style');
				}

				if (data.offset === 0) {
					if (carousel) {
						carousel.slides.forEach(function(slide) {
							carousel.removeSlide(slide.index);
						});
					} else {
						wrapper.children().remove();
					}
				}

				if (response['result']) {

					var posts = $(response['result']);

					if (carousel) {
						posts.each(function() {
							carousel.appendSlide({
								html: this.innerHTML
							});
						});
					} else {
						wrapper.append(posts);
					}

					if (animate) {
						wrapper.removeAttr('style');
						heightNew = wrapper.height();
						wrapper.css('height', heightOld);
					}

					button.addClass('is_hidden');

					if (data.number > 0) {

						data.offset = data.offset + data.number;

						button.data('loader', data);

						if (response['more']) {
							button.removeClass('is_hidden');
						}

					}

					Twee.runModules(section);

					if (animate) {
						wrapper.animate({height: heightNew}, 400, function() {
							wrapper.removeAttr('style');
						});
					}

				} else {

					button.addClass('is_hidden');

					if (animate) {
						wrapper.removeAttr('style');
					}

				}

			});

		});

	});

});