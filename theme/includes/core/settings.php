<?php
/**
 * Settings library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Get current theme setting
 *
 * @param bool|string $group Setting group
 * @param bool|string $name  Setting name
 * @param bool|string $key   Setting key
 *
 * @return array|bool
 */

function tw_get_setting($group = false, $name = false, $key = false) {

	global $tw_settings;

	$result = false;

	if ($group and $name) {

		if ($key) {

			if (isset($tw_settings[$group][$name][$key])) {
				$result = $tw_settings[$group][$name][$key];
			}

		} else {

			if (isset($tw_settings[$group][$name])) {
				$result = $tw_settings[$group][$name];
			}

		}

	} elseif ($group) {

		if (isset($tw_settings[$group])) {
			$result = $tw_settings[$group];
		}

	} else {

		$result = $tw_settings;

	}
	
	return $result;

}


/**
 * Set theme setting
 *
 * @param string $group Setting group
 * @param string $name  Setting name
 * @param $value
 */

function tw_set_setting($group, $name, $value) {

	global $tw_settings;

	if (empty($tw_settings)) {
		$tw_settings = array();
	}

	if ($name) {

		if ($group) {
			$tw_settings[$group][$name] = $value;
		} else {
			$tw_settings[$name] = $value;
		}

	}

}