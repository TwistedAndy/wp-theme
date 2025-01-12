Twee.addModule('forms', '.form_box, .comment_box', function($, forms) {

	if (forms.hasClass('form_box') && !forms.is('form')) {
		forms = $('form', this);
	} else {
		forms = $('form.form_box, form.comment-form', this);
	}

	forms.not('.skip_processing').each(function() {

		if (!Twee.runOnce(this, 'forms')) {
			return;
		}

		var form = $(this),
			message,
			button = $('[type="submit"]', form);

		form.on('submit', function(e) {

			var data = form.serializeArray();

			if ($('[name="action"]', form).length === 0) {

				var action = 'email_handler';

				if (form.hasClass('comment-form')) {
					action = 'comment_add';
				}

				data.push({
					name: 'action',
					value: action
				});

			}

			data.push({
				name: 'noncer',
				value: tw_template.nonce
			});

			$.ajax(tw_template.ajaxurl, {
				data: data,
				type: 'post',
				dataType: 'json',
				beforeSend: function() {
					button.prop('disabled', true).addClass('is_loading');
				},
			}).always(function() {
				button.prop('disabled', false).removeClass('is_loading');
			}).done(processResponse);

			e.preventDefault();

			e.stopPropagation();

			return false;

		});

		form.on('change', 'input:file', function() {

			var data = new FormData(),
				file = this;

			data.append('action', 'email_attachment');

			data.append(file.name, file.files[0]);

			$.ajax(tw_template.ajaxurl, {
				type: 'post',
				data: data,
				dataType: 'json',
				processData: false,
				contentType: false,
				beforeSend: function() {
					button.prop('disabled', true).addClass('is_loading');
				},
				xhr: function() {
					var xhr = new XMLHttpRequest();
					xhr.upload.addEventListener('progress', function(e) {
						var percent = 0;
						if (e.lengthComputable && e.total) {
							percent = Math.round((e.loaded / e.total) * 100);
							console.log('Uploading: ' + percent + '%');
						}
					}, false);
					return xhr;
				},
			}).always(function() {

				button.prop('disabled', false).removeClass('is_loading');

			}).done(processResponse);

			return false;

		});

		form.on('click', '.remove', function() {

			var data = form.serializeArray();

			data.push({
				name: 'action',
				value: 'email_remove'
			});

			data.push({
				name: 'filename',
				value: $(this).data('name')
			});

			data.push({
				name: 'noncer',
				value: tw_template.nonce
			});

			$.ajax(tw_template.ajaxurl, {
				data: data,
				type: 'post',
				dataType: 'json',
				beforeSend: function() {
					button.prop('disabled', true).addClass('is_loading');
				},
			}).always(function() {

				button.prop('disabled', false).removeClass('is_loading');

			}).done(processResponse);

		});

		function processResponse(data) {

			$('.error, .success', form).remove();

			if (data.link && data.link.length > 0) {
				window.location.href = data.link;
			}

			if (data.errors) {
				for (let i in data.errors) {
					if (data.errors.hasOwnProperty(i)) {
						message = $('<div class="error">' + data['errors'][i] + '</div>');
						$('[name=' + i + ']', form).closest('.field').append(message);
						message.hide().slideDown();
					}
				}
			}

			if (data.files) {
				for (let i in data.files) {
					if (data.files.hasOwnProperty(i)) {
						var field = $('[name=' + i + ']', form).closest('.field');
						field.siblings('.notify').slideUp(400, function() {
							$(this).remove();
						});
						message = $('<div class="notify">' + data['files'][i] + '</div>');
						field.after(message);
						message.hide().slideDown();
					}
				}
			}

			if (data.text && data.text.length > 0) {
				message = $('<div class="success">' + data.text + '</div>');
				form.append(message);
				message.hide().slideDown();
				form[0].reset();
			}

		}

	});

	/**
	 * Autoresize textarea fields
	 */
	forms.on('input change', 'textarea', function() {
		this.style.minHeight = 'initial';
		this.style.minHeight = (this.scrollHeight) + 'px';
	});

});