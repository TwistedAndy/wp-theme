<?php
/**
 * File loader library
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */

/**
 * Include template core files and modules
 *
 * @param string $folder
 * @param string $file
 *
 * @return bool
 */

function tw_load_file($folder, $file) {

	if (empty($folder)) {
		$folder = '';
	} else {
		$folder .= '/';
	}

	$filename = TW_INC . '/' . $folder . $file . '.php';

	$filename = apply_filters('tw_load_file', $filename, $folder, $file);

	if (is_file($filename)) {

		include_once($filename);

		return true;

	} else {

		return false;

	}

}


/**
 * Include enabled modules
 *
 * @param string $folder
 * @param string $files
 */

function tw_load_files($folder, $files = '') {

	if (is_string($folder) and empty($files)) {
		$files = tw_get_setting($folder);
	}

	if (is_array($files)) {

		foreach ($files as $file => $enabled) {

			if ($enabled) {
				tw_load_file($folder, $file);
			}

		}

	}

}


/**
 * Include core files
 */

$files = array(
	'assets',
	'content',
	'settings',
	'taxonomy',
	'thumbs',
	'widget'
);

foreach ($files as $file) {
	tw_load_file('core', $file);
}


/**
 *  Include theme settings
 */

tw_load_file('..', 'settings');


/**
 * Include theme modules
 */

tw_load_files('modules');


/**
 * Include custom AJAX handlers
 */

tw_load_files('ajax');