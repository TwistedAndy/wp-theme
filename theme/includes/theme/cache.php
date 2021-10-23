<?php

/**
 *  Add a value to the cache
 *
 * @param string $key
 * @param mixed  $value
 */
function tw_cache_set($key, $value, $persistent = true, $expire = 600) {

	global $tw_acf_cache;

	if (empty($tw_acf_cache)) {
		$tw_acf_cache = [];
	}

	$tw_acf_cache[$key] = $value;

	if ($persistent) {
		wp_cache_set($key, $value, 'aws', $expire);
	}

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

	if (isset($tw_acf_cache[$key])) {
		$value = $tw_acf_cache[$key];
	} else {
		$value = wp_cache_get($key, 'aws');
		$tw_acf_cache[$key] = $value;
	}

	return $value;

}