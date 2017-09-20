<?php
/**
 * Settings library
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
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

	if ($group and $name and isset($tw_settings[$group][$name])) {

		if ($key and isset($tw_settings[$group][$name][$key])) {

			$result = $tw_settings[$group][$name][$key];

		} else {

			$result = $tw_settings[$group][$name];

		}

	} elseif ($group and isset($tw_settings[$group])) {

		$result = $tw_settings[$group];

	} elseif ($name == false and $group == false) {

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

	if ($name and $group) {
		$tw_settings[$group][$name] = $value;
	} elseif ($name) {
		$tw_settings[$name] = $value;
	}

}
