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
 * @param string                $name   Template part name
 * @param array|WP_Post|WP_Term $item   Array with data
 * @param string                $folder Folder with template part
 */

function tw_template_part($name, $item = array(), $folder = 'parts') {

	if ($folder) {
		$folder = trailingslashit($folder);
	}

	$filename = TW_ROOT . '/' . $folder . $name . '.php';

	if (is_array($item)) {
		extract($item);
	}

	if (file_exists($filename)) {

		include $filename;

	}

}