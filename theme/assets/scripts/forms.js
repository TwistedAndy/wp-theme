/* Form processing */

jQuery(function($) {

	$('.form_box form, form.form_box, form.comment-form').each(function() {

		var form = $(this), message,
			button = $('[type="submit"]', form);

		form.on('submit', function(e) {

			var data = $(':input', form).serializeArray();

			var action = 'feedback';

			if (form.hasClass('comment-form')) {
				action = 'comment';
			}

			if ($('[name="action"]', form).length === 0) {
				data.push({
					name: 'action',
					value: action
				});
			}

			data.push({
				name: 'noncer',
				value: template.nonce
			});

			$.ajax({
				url: template.ajaxurl,
				data: data,
				type: 'post',
				dataType: 'json',
				beforeSend: function() {
					button.prop('disabled', true).addClass('is_loading');
				},
				complete: function() {
					button.prop('disabled', false).removeClass('is_loading');
				},
				success: processResponse
			});

			e.preventDefault();

			e.stopPropagation();

			return false;

		});


		form.on('change', 'input:file', function() {

			var data = new FormData(), file = this;

			data.append('action', 'process_file');

			data.append(file.name, file.files[0]);

			$.ajax({
				url: template.ajaxurl,
				type: 'post',
				data: data,
				dataType: 'json',
				processData: false,
				contentType: false,
				beforeSend: function() {
					button.prop('disabled', true).addClass('is_loading');
				},
				complete: function() {
					button.prop('disabled', false).removeClass('is_loading');
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
				success: processResponse
			});

			return false;

		});

		form.on('click', '.remove', function() {

			var data = form.serializeArray();

			data.push({
				name: 'action',
				value: 'remove_file'
			});

			data.push({
				name: 'filename',
				value: $(this).data('name')
			});

			data.push({
				name: 'noncer',
				value: template.nonce
			});

			$.ajax({
				url: template.ajaxurl,
				data: data,
				type: 'post',
				dataType: 'json',
				beforeSend: function() {
					button.prop('disabled', true).addClass('is_loading');
				},
				complete: function() {
					button.prop('disabled', false).removeClass('is_loading');
				},
				success: processResponse
			});

		});

		function processResponse(data) {

			$('.error, .success', form).remove();

			if (data.link) {
				window.location.href = data.link;
			}

			if (data.errors) {
				for (let i in data.errors) {
					if (data.errors.hasOwnProperty(i)) {
						message = $('<div class="error">' + data['errors'][i] + '</div>');
						$('[name=' + i + ']', form).parents('.field').append(message);
						message.hide().slideDown();
					}
				}
			}

			if (data.files) {
				for (let i in data.files) {
					if (data.files.hasOwnProperty(i)) {
						var field = $('[name=' + i + ']', form).parents('.field');
						field.siblings('.notify').slideUp(400, function(){
							$(this).remove();
						});
						message = $('<div class="notify">' + data['files'][i] + '</div>');
						field.after(message);
						message.hide().slideDown();
					}
				}
			}

			if (data.text) {
				message = $('<div class="success">' + data.text + '</div>');
				form.append(message);
				message.hide().slideDown();
				form[0].reset();
			}

		}

	});

});