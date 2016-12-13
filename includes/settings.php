<?php
/**
 * Settings library
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */


/**
 * Get current theme setting
 *
 * @param bool|string $group Setting group
 * @param bool|string $name  Setting name
 *
 * @return array|bool
 */

function tw_get_setting($group = false, $name = false) {

	global $tw_settings;

	if ($name and $group and isset($tw_settings[$group][$name])) {
		return $tw_settings[$group][$name];
	} elseif ($group and isset($tw_settings[$group])) {
		return $tw_settings[$group];
	} elseif ($name == false and $group == false) {
		return $tw_settings;
	} else {
		return false;
	}

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
