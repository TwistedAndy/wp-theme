<?php
/**
 * Metadata Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/**
 * Get array with selected metadata keys
 *
 * @param string $meta_type
 * @param string $meta_key
 * @param bool   $decode
 *
 * @return array
 */
function tw_metadata($meta_type = 'post', $meta_key = '_thumbnail_id', $decode = false) {

	$cache_key = 'meta_' . $meta_key;
	$cache_group = 'twee_meta_' . $meta_type;

	if ($decode) {
		$cache_key .= '_decoded';
	}

	$meta = wp_cache_get($cache_key, $cache_group);

	if (is_array($meta)) {
		return $meta;
	}

	$cached_keys = wp_cache_get('meta_keys', $cache_group);

	if (!is_array($cached_keys)) {
		$cached_keys = [];
	}

	$cached_keys[] = $meta_key;

	wp_cache_set('meta_keys', array_unique($cached_keys), $cache_group);

	$meta = [];

	$db = tw_app_database();

	if ($meta_type == 'term') {
		$table = $db->termmeta;
		$key = 'term_id';
	} elseif ($meta_type == 'user') {
		$table = $db->usermeta;
		$key = 'user_id';
	} elseif ($meta_type == 'comment') {
		$table = $db->commentmeta;
		$key = 'comment_id';
	} else {
		$table = $db->postmeta;
		$key = 'post_id';
	}

	$result = $db->get_results($db->prepare("SELECT meta.{$key}, meta.meta_value FROM {$table} AS meta WHERE meta.meta_key = %s", $meta_key), ARRAY_A);

	if ($result) {
		if ($decode) {
			foreach ($result as $row) {
				$meta[$row[$key]] = maybe_unserialize($row['meta_value']);
			}
		} else {
			foreach ($result as $row) {
				$meta[$row[$key]] = $row['meta_value'];
			}
		}
	}

	wp_cache_set($cache_key, $meta, $cache_group);

	return $meta;

}


/**
 * Clean cached meta data
 *
 * @param string $meta_type
 * @param string $meta_key
 * @param string $object_id
 *
 * @return void
 */
function tw_metadata_clean($meta_type, $meta_key, $object_id) {

	$cache_group = 'twee_meta_' . $meta_type;

	$cached_keys = wp_cache_get('meta_keys', $cache_group);

	if (!is_array($cached_keys)) {
		$cached_keys = [];
	}

	if (in_array($meta_key, $cached_keys)) {

		$cache_key = 'meta_' . $meta_key;

		tw_app_set($cache_key, null, $cache_group);
		tw_app_set($cache_key . '_decoded', null, $cache_group);
		tw_app_clear($cache_group . '_' . $object_id);

		wp_cache_delete($cache_key, $cache_group);
		wp_cache_delete($cache_key . '_decoded', $cache_group);

	}

}


/**
 * Clear meta caches
 */
foreach (['post', 'term', 'user', 'comment'] as $meta_type) {

	add_action('added_' . $meta_type . '_meta', function($meta_id, $object_id, $meta_key) use ($meta_type) {
		tw_metadata_clean($meta_type, $meta_key, $object_id);
	}, 10, 3);

	add_action('updated_' . $meta_type . '_meta', function($meta_id, $object_id, $meta_key) use ($meta_type) {
		tw_metadata_clean($meta_type, $meta_key, $object_id);
	}, 10, 3);

	add_action('deleted_' . $meta_type . '_meta', function($meta_id, $object_id, $meta_key) use ($meta_type) {
		tw_metadata_clean($meta_type, $meta_key, $object_id);
	}, 10, 3);

}