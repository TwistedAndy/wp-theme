<?php
/**
 * Load and store the data in session
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */

if (class_exists('WooCommerce')) {

	add_action('woocommerce_init', function() {

		$session = WooCommerce::instance()->session;

		if ($session instanceof WC_Session_Handler and !$session->get_session_cookie()) {
			if (is_user_logged_in()) {
				$session->init_session_cookie();
			} else {
				$session->set_customer_session_cookie(true);
			}
		}

	});

} elseif (!session_id()) {

	session_start();

}


/**
 * Get the variable stored in session
 *
 * @param string $key
 *
 * @return array|bool|number|string
 */
function tw_session_get($key) {

	$result = false;

	if (class_exists('WooCommerce') and class_exists('WC_Session_Handler')) {

		$session = WooCommerce::instance()->session;

		if ($session instanceof WC_Session_Handler) {
			$result = $session->get($key);
		}

	} elseif (isset($_SESSION[$key])) {

		$result = $_SESSION[$key];

	}

	return $result;

}


/**
 * Store data in session
 *
 * @param string                   $key
 * @param array|bool|number|string $data
 */
function tw_session_set($key, $data) {

	if (class_exists('WooCommerce')) {

		$session = WooCommerce::instance()->session;

		if ($session instanceof WC_Session_Handler) {
			$session->set($key, $data);
			$session->save_data();
		}

	} else {

		$_SESSION[$key] = $data;

	}

}


/**
 * Get the stored file
 *
 * @param bool|string $name Name of the session group
 *
 * @return array
 */
function tw_session_get_file($name = false) {

	$files = tw_session_get('uploads');

	if (!is_array($files)) {
		$files = [];
	}

	$file = false;

	if ($name === false) {
		$file = $files;
	} elseif (!empty($files[$name]) and is_array($files[$name])) {
		$file = $files[$name];
	}

	return $file;

}


/**
 * Update the stored file
 *
 * @param string $name   Name of the session group
 * @param bool|array  $file Array with file information
 */
function tw_session_set_file($name, $file) {

	$files = tw_session_get('uploads');

	if (!is_array($files)) {
		$files = [];
	}

	if (empty($file) and isset($files[$name])) {
		unset($files[$name]);
	} else {
		$files[$name] = $file;
	}

	tw_session_set('uploads', $files);

}