<?php
/**
 * Metadata Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/**
 * Get array with selected metadata keys
 *
 * @param array|string $keys
 *
 * @return array
 */
function tw_metadata($type = 'post', $keys = '_thumbnail_id') {

	$cache_key = $type . '_meta_';
	$cache_group = 'twee_' . $type . '_meta';

	$where = '';

	if (is_array($keys)) {
		sort($keys);
		$cache_key .= 'array_' . implode('_', $keys);
		$where = " WHERE meta.meta_key IN ('" . implode("', '", $keys) . "')";
	} elseif (is_string($keys)) {
		$cache_key .= 'string_' . $keys;
		$where = " WHERE meta.meta_key = '" . $keys . "'";
	}

	$meta = tw_app_get($cache_key . $cache_group);

	if (is_array($meta)) {
		return $meta;
	}

	$meta = wp_cache_get($cache_key, $cache_group);

	if (!is_array($meta)) {

		$cached_keys = wp_cache_get('meta_keys', $cache_group);

		if (!is_array($cached_keys)) {
			$cached_keys = [];
		}

		if (is_array($keys)) {
			$cached_keys = array_merge($cached_keys, $keys);
		} else {
			$cached_keys[] = $keys;
		}

		wp_cache_set('meta_keys', array_unique($cached_keys), $cache_group);

		$meta = [];

		$db = tw_app_database();

		if ($type == 'term') {
			$table = $db->termmeta;
			$key = 'term_id';
		} elseif ($type == 'user') {
			$table = $db->usermeta;
			$key = 'user_id';
		} elseif ($type == 'comment') {
			$table = $db->commentmeta;
			$key = 'comment_id';
		} else {
			$table = $db->postmeta;
			$key = 'post_id';
		}

		$result = $db->get_results("SELECT meta.{$key}, meta.meta_key, meta.meta_value FROM {$table} AS meta {$where}", ARRAY_A);

		if ($result) {
			if (is_string($keys)) {
				foreach ($result as $row) {
					if (!empty($row[$key]) and isset($row['meta_key'])) {
						$meta[$row[$key]] = maybe_unserialize($row['meta_value']);
					}
				}
			} else {
				foreach ($result as $row) {
					if (!empty($row[$key]) and isset($row['meta_key'])) {
						if (!isset($meta[$row[$key]])) {
							$meta[$row[$key]] = [];
						}
						$meta[$row[$key]][$row['meta_key']] = maybe_unserialize($row['meta_value']);
					}
				}
			}
		}

		tw_app_set($cache_key . $cache_group, $meta);

		wp_cache_set($cache_key, $meta, $cache_group);

	}

	return $meta;

}


/**
 * Clean only cached meta keys
 *
 * @param string $type
 * @param string $key
 *
 * @return void
 */
function tw_metadata_clean($type, $key) {

	$cache_group = 'twee_' . $type . '_meta';
	$cached_keys = wp_cache_get('meta_keys', $cache_group);

	if (!is_array($cached_keys)) {
		$cached_keys = [];
	}

	if (in_array($key, $cached_keys)) {
		wp_cache_flush_group($cache_group);
	}

}


/**
 * Clear meta caches
 */
foreach (['post', 'term', 'post', 'comment'] as $type) {

	add_action('added_' . $type . '_meta', function($meta_id, $object_id, $meta_key) use ($type) {
		tw_metadata_clean($type, $meta_key);
	}, 10, 3);

	add_action('updated_' . $type . '_meta', function($meta_id, $object_id, $meta_key) use ($type) {
		tw_metadata_clean($type, $meta_key);
	}, 10, 3);

	add_action('deleted_' . $type . '_meta', function($meta_id, $object_id, $meta_key) use ($type) {
		tw_metadata_clean($type, $meta_key);
	}, 10, 3);

}