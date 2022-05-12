<?php

/**
 *  Add a value to the cache
 *
 * @param string $key
 * @param mixed  $value
 */
function tw_cache_set($key, $value) {

	global $tw_acf_cache;

	if (empty($tw_acf_cache)) {
		$tw_acf_cache = [];
	}

	$tw_acf_cache[$key] = $value;

}


/**
 * Retrieve a value from the memory cache
 *
 * @param string $key
 *
 * @return mixed
 */
function tw_cache_get($key) {

	global $tw_acf_cache;

	$value = null;

	if (isset($tw_acf_cache[$key])) {
		$value = $tw_acf_cache[$key];
	}

	return $value;

}