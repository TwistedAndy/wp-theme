<?php
/**
 * Validate and send email
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

add_action('wp_ajax_nopriv_request_call', 'tw_ajax_callback');
add_action('wp_ajax_request_call', 'tw_ajax_callback');

function tw_ajax_callback() {

	if (isset($_POST['nonce']) and wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {

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

		if (count($errors) == 0) {

			$to = get_option('admin_email');

			$subject = "Сообщение от посетителя";
			$message = "
			<p><b>Имя:</b> " . $_POST['name'] . "</p>
			<p><b>E-mail:</b> " . $_POST['email'] . "</p>
			<p><b>Телефон:</b> " . $_POST['phone'] . "</p>
			<p><b>Сообщение:</b> " . $_POST['message'] . "</p>";

			$headers = array();
			$headers[] = 'Content-type: text/html; charset=utf-8';

			if (wp_mail($to, $subject, $message, $headers)) {

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

<form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
	<input type="text" value="" placeholder="Как вас зовут" name="name" />
	<input type="text" value="" placeholder="Ваш e-mail" name="email" />
	<textarea cols="40" rows="5" placeholder="Сообщение" name="message"></textarea>
	<input type="submit" value="Отправить" />
	<input type="hidden" name="action" value="request_call" />
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ajax-nonce'); ?>" />
</form>

<script type="text/javascript">

jQuery(function($){

	$('form').submit(function(e){

		var form = $(this), el;

		$.ajax({
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			type: 'post',
			dataType: 'json',
			data: $('input:text, input:hidden, input:checked, textarea, select', form),
			success: function(data) {

				$('.error', form).remove();

				$('input, textarea, select', form).removeClass('incorrect');

				if (data['errors']) {
					for (i in data['errors']) {
						el = $('<div class="error">' + data['errors'][i] + '</div>');
						$('[name=' + i + ']', form).addClass('incorrect').after(el);
						el.hide();
						el.slideDown();
					}
				}

				if (data['text']) {
					el = $('<div class="success">' + data['text'] + '</div>');
					form.append(el);
					el.hide();
					el.slideDown();
					$('input[type="text"], textarea, select', form).val('');
				}

			}
		});

		e.preventDefault();

		return false;

	});

});

</script>

*/