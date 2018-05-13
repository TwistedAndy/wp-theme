<?php
/**
 * File loader library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */

/**
 * Include some or all files in folder
 *
 * @param string            $folder Full folder path
 * @param string|array|bool $files  Array with file names, single file name or true to load all files
 *
 * @return bool
 */

function tw_load_files($folder, $files = true) {

	if (empty($files) or empty($folder) or !is_dir($folder)) {

		return false;

	}

	if (is_string($files)) {

		$files = array($files);

	} elseif (is_bool($files)) {

		$files = scandir($folder);

		if (is_array($files)) {

			$result = array();

			foreach ($files as $file) {

				if (strpos($file, '.php') !== false) {

					$result[] = str_replace('.php', '', $file);

				}

			}

			$files = $result;

		}

	}

	if (is_array($files)) {

		foreach ($files as $file) {

			$filename = $folder . '/' . $file . '.php';

			if (is_file($filename)) {

				include_once($filename);

			}

		}

	}

	return true;

}


/**
 * Include the core files
 */

$files = array(
	'assets',
	'content',
	'settings',
	'taxonomy',
	'thumbs',
	'widget'
);

tw_load_files(TW_INC . '/core', $files);


/**
 *  Include the theme settings
 */

tw_load_files(TW_ROOT, 'settings');


/**
 * Include the theme modules
 */

tw_load_files(TW_INC . '/modules');


/**
 * Include the custom AJAX handlers
 */

tw_load_files(TW_INC . '/ajax');