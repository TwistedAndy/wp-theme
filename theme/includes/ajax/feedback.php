<?php
/**
 * Validate and send email
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/*
add_action('wp_ajax_nopriv_feedback', 'tw_ajax_feedback');
add_action('wp_ajax_feedback', 'tw_ajax_feedback');
*/

function tw_ajax_feedback() {

	$result = [
		'text' => '',
		'errors' => []
	];

	if (!empty($_POST['type']) and isset($_POST['noncer']) and wp_verify_nonce($_POST['noncer'], 'ajax-nonce')) {

		foreach ($_POST as $k => $v) {

			if (is_array($v)) {
				$_POST[$k] = array_map('htmlspecialchars', $v);
			} else {
				$_POST[$k] = htmlspecialchars($v);
			}

		}

		$recipient = get_option('admin_email');

		$errors = [];

		$required = [
			'name' => '#^[a-zA-Z0-9 -.]{2,}$#ui',
			'subject' => '#^.{2,}$#ui',
			'email' => '#^[^\@]+@.*\.[a-z]{2,6}$#i',
			'phone' => '#^[0-9 . -+ ()]{7,12}$#i',
		];

		$fields = [
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'phone' => 'Phone',
			'email' => 'E-mail',
			'subject' => 'Subject',
			'message' => 'Message',
		];

		$type = intval($_POST['type']);

		if ($type == 1) {

			$subject = 'Request from ' . $_POST['name'];

			$required['message'] = [
				'error' => 'Please type a message',
				'pattern' => '#^.{10,}$#ui'
			];

		} else {

			$subject = 'Request from a client';

		}

		foreach ($required as $key => $field) {

			$label = $fields[$key];

			if (!is_array($field)) {
				$field = ['pattern' => $field];
			}

			if (empty($field['error'])) {
				$field['error'] = $label . ' is not valid';
			}

			if (empty($_POST[$key])) {
				$errors[$key] = $label . ' is required';
			} elseif (!preg_match($field['pattern'], $_POST[$key])) {
				$errors[$key] = $field['error'];
			}

		}

		if (count($errors) == 0) {

			$message = [];

			foreach ($fields as $key => $field) {

				if (!empty($_POST[$key])) {

					$value = $_POST[$key];

					$message[] = '<p><b>' . $field . ':</b> ' . $value . '</p>';

				}

			}

			$headers = [];

			$headers[] = 'Content-type: text/html; charset=utf-8';

			$files = [];

			foreach (['artwork'] as $name) {

				$file = tw_session_get_file($name);

				if ($file and file_exists($file['file'])) {
					$files[$name] = $file;
				}

			}

			if (wp_mail($recipient, $subject, implode("\n", $message), $headers, $files)) {

				if ($files) {
					foreach ($files as $name => $file) {
						unlink($file);
						tw_session_set_file($name, false);
					}
				}

				$result['text'] = __('Thanks! We will contact you soon!', 'twee');

			} else {

				$result['text'] = __('Error. Please, try again a bit later', 'twee');

			}

		} else {

			$result['errors'] = $errors;

		}

	}

	wp_send_json($result);

}

/*
add_action('wp_ajax_nopriv_process_file', 'tw_ajax_process_file');
add_action('wp_ajax_process_file', 'tw_ajax_process_file');
*/

function tw_ajax_process_file() {

	$errors = [];

	$result = [
		'text' => '',
		'errors' => [],
		'files' => []
	];

	$files = [
		'artwork' => 'Please attach the artwork',
	];

	add_filter('upload_dir', function($dir) {

		if (!is_array($dir)) {
			$dir = [];
		}
	
		$dir['path'] = $dir['basedir'] . '/cache/emails';
		$dir['url'] = $dir['basedir'] . '/cache/emails';
		$dir['subdir'] = '/cache/emails';
	
		return $dir;
	
	});

	foreach ($files as $key => $value) {

		if (!empty($_FILES[$key])) {

			$file = tw_session_get_file($key);

			$file_id = md5($_FILES[$key]['name'] . $_FILES[$key]['size']);

			if (!empty($file) and file_exists($file['file'])) {

				if ($file['id'] === $file_id) {
					continue;
				} else {
					unlink($file['file']);
					tw_session_set_file($key, false);
				}

			}

			$args = [
				'test_form' => false,
			];

			if ($key === 'artwork') {

				$args['mimes'] = [
					'jpg|jpeg|jpe' => 'image/jpeg',
					'gif' => 'image/gif',
					'png' => 'image/png',
					'bmp' => 'image/bmp',
					'tiff|tif' => 'image/tiff',
					'pdf' => 'application/pdf',
				];

				$args['unique_filename_callback'] = function($directory, $name, $ext) {
					return 'cache_' . date('m_d_Y_') . substr(md5($name . time()), 0, 8) . $ext;
				};

			}

			$file = wp_handle_upload($_FILES[$key], $args);

			if ($file and empty($file['error'])) {

				$file['id'] = $file_id;

				$file['name'] = htmlspecialchars($_FILES[$key]['name']);

				tw_session_set_file($key, $file);

				$result['files'][$key] = sprintf(__('File <b>%1$s</b> was uploaded. <span class="remove" data-name="%2$s" aria-label="Remove"></span>', 'twee'), $file['name'], $key);

			} else {

				if (empty($file['error'])) {
					$file['error'] = __('Something went wrong', 'twee');
				}

				$errors[$key] = $file['error'];
				$result['files'][$key] = '';

			}

		}

	}

	$result['errors'] = $errors;

	wp_send_json($result);

}


add_action('wp_ajax_nopriv_remove_file', 'tw_ajax_remove_file');
add_action('wp_ajax_remove_file', 'tw_ajax_remove_file');

function tw_ajax_remove_file() {

	$result = [
		'text' => '',
		'errors' => [],
		'files' => []
	];

	if (!empty($_REQUEST['filename'])) {

		$filename = htmlspecialchars($_REQUEST['filename']);

		$file = tw_session_get_file($filename);

		if (!empty($file) and file_exists($file['file'])) {

			$result['files'][$filename] = 'File <b>' . $file['name'] . '</b> was removed.';

			unlink($file['file']);

			tw_session_set_file($filename, false);

		}

	}

	wp_send_json($result);

}