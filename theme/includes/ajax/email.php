<?php
/**
 * Validate and send email
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

/*
add_action('wp_ajax_nopriv_feedback', 'tw_ajax_feedback');
add_action('wp_ajax_feedback', 'tw_ajax_feedback');
*/

function tw_ajax_feedback() {

	if (isset($_POST['noncer']) and wp_verify_nonce($_POST['noncer'], 'ajax-nonce')) {

		$errors = array();

		$fields = array(
			'name' => array(
				'error' => 'Неверно указано имя',
				'pattern' => '#^[a-zA-Zа-яА-Я0-9 -.]{2,}$#ui'
			),
			'email' => array(
				'error' => 'Неверно указан e-mail',
				'pattern' => '#^[^\@]+@.*\.[a-z]{2,6}$#i'
			),
			'message' => array(
				'error' => 'Введите сообщение',
				'pattern' => '#^.{4,}$#i'
			),
			'phone' => array(
				'error' => 'Неверно указан телефон',
				'pattern' => '#^[0-9 +\- ()]{4,}$#i'
			)
		);

		foreach ($_POST as $k => $v) {
			$_POST[$k] = htmlspecialchars($v);
		}

		foreach ($fields as $k => $v) {
			if (isset($_POST[$k]) and !preg_match($v['pattern'], $_POST[$k]) and !(isset($v['empty']) and $v['empty'] and $_POST[$k] == '')) {
				$errors[$k] = $v['error'];
			}
		}

		if (empty($_POST['agree'])) {
			$errors['agree'] = 'Вы должны принять пользовательское соглашение';
		}

		if (count($errors) == 0) {

			$to = get_option('admin_email');

			$subject = "Сообщение от посетителя";

			$message = array();
			$message[] = '<p><b>Имя:</b> ' . $_POST['name'] . '</p>';
			$message[] = '<p><b>E-mail:</b> ' . $_POST['email'] . '</p>';
			$message[] = '<p><b>Телефон:</b> ' . $_POST['phone'] . '</p>';
			$message[] = '<p><b>Сообщение:</b> ' . $_POST['message'] . '</p>';

			$headers = array();
			$headers[] = 'Content-type: text/html; charset=utf-8';

			if (wp_mail($to, $subject, implode("\n", $message), $headers)) {

				echo(json_encode(array('text' => "Ваш запрос был успешно отправлен")));

			} else {

				echo(json_encode(array('text' => "Ошибка. Запрос не отправлен из-за ошибки сервера")));

			}

		} else {

			echo(json_encode(array('errors' => $errors)));

		}

	}

	exit();

}

/*

<script type="text/javascript">

	jQuery(function($) {

	$('form').submit(function(e) {

		var form = $(this), message, data = form.serializeArray();

		data.push({
			name: 'action',
			value: 'feedback'
		});

		data.push({
			name: 'noncer',
			value: template.nonce
		});

		$.ajax({
			url: template.ajaxurl,
			type: 'post',
			dataType: 'json',
			data: data,
			success: function(data) {

				$('.error, .success', form).remove();

				if (data['errors']) {
					for (var i in data['errors']) {
						message = $('<div class="error">' + data['errors'][i] + '</div>');
						$('[name=' + i + ']', form).parent().append(message);
						message.hide().slideDown();
					}
				}

				if (data['text']) {
					message = $('<div class="success">' + data['text'] + '</div>');
					form.append(message);
					message.hide().slideDown();
					form[0].reset();
				}

			}
		});

		e.preventDefault();

		return false;

	});

});

</script>

*/