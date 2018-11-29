<?php
/**
 * Template processing library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */


/**
 * Load template part
 *
 * @param string                $name Template part name
 * @param array|WP_Post|WP_Term $item Array with data
 */

function tw_template_part($name, $item = array()) {

	$filename = TW_ROOT . '/parts/' . $name . '.php';

	if (file_exists($filename)) {

		include $filename;

	}

}