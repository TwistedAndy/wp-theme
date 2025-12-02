<?php
/**
 * Metadata Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
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

	if (!in_array($meta_type, ['post', 'term', 'user', 'comment'])) {
		return [];
	}

	$chunk_size = 100;
	$cache_key = $meta_key;
	$cache_group = 'twee_meta_' . $meta_type;

	$chunk_map = wp_cache_get($cache_key . '_chunks', $cache_group);

	if (is_array($chunk_map) and $chunk_map) {

		$meta = [];

		foreach ($chunk_map as $index => $last_element) {

			$chunk_data = wp_cache_get($cache_key . '_chunk_' . $index, $cache_group);;

			if (is_array($chunk_data)) {
				$meta += $chunk_data;
			}

		}

	} else {

		$meta = wp_cache_get($cache_key, $cache_group);

	}

	if (is_array($meta)) {
		if ($decode) {
			foreach ($meta as $object_id => $meta_value) {
				$meta[$object_id] = maybe_unserialize($meta_value);
			}
		}
		return $meta;
	}

	$meta = [];

	$db = tw_app_database();

	$key = $meta_type . '_id';
	$table = $db->prefix . $meta_type . 'meta';
	$result = $db->get_results($db->prepare("SELECT meta.{$key}, meta.meta_value FROM {$table} AS meta WHERE meta.meta_key = %s ORDER BY meta.{$key} DESC", $meta_key), ARRAY_A);

	$chunks = [];
	$chunk_map = [];

	if (is_array($result)) {

		foreach ($result as $line => $row) {

			$index = intdiv($line, $chunk_size);
			$object_id = (int) $row[$key];

			if (!isset($chunks[$index])) {
				$chunks[$index] = [];
				$chunk_map[$index] = $object_id;
			}

			$chunks[$index][$object_id] = (string) $row['meta_value'];

		}

	}

	if (count($chunks) > 1) {

		wp_cache_set($cache_key . '_chunks', $chunk_map, $cache_group);

		foreach ($chunks as $index => $chunk) {
			wp_cache_set($cache_key . '_chunk_' . $index, $chunk, $cache_group);
			$meta += $chunk;
		}

	} elseif (count($chunks) == 1) {

		$meta = reset($chunks);

		if ($chunk_map) {
			wp_cache_delete($cache_key . '_chunks', $cache_group);
		}

	}

	wp_cache_set($cache_key, $meta, $cache_group);

	if ($decode) {
		foreach ($meta as $object_id => $meta_value) {
			$meta[$object_id] = maybe_unserialize($meta_value);
		}
	}

	return $meta;

}


/**
 * Get metadata for a record
 *
 * @param string $meta_type
 * @param int    $object_id
 * @param string $meta_key
 *
 * @return mixed
 */
function tw_metadata_get($meta_type, $object_id, $meta_key) {

	if (!TW_CACHE) {
		return get_metadata_raw($meta_type, $object_id, $meta_key, true);
	}

	if (!in_array($meta_type, ['post', 'term', 'user', 'comment'])) {
		return null;
	}

	$object_id = absint($object_id);

	$cache = wp_cache_get($object_id, $meta_type . '_meta');

	if (is_array($cache) and isset($cache[$meta_key]) and isset($cache[$meta_key][0])) {
		return maybe_unserialize($cache[$meta_key][0]);
	}

	$cache_key = tw_metadata_cache_key($meta_type, $object_id, $meta_key);
	$cache_group = 'twee_meta_' . $meta_type;

	$meta_map = wp_cache_get($cache_key, $cache_group);;

	if (!is_array($meta_map)) {
		$meta_map = tw_metadata($meta_type, $meta_key, false);
	}

	if (is_array($meta_map) and isset($meta_map[$object_id])) {
		if (is_serialized($meta_map[$object_id])) {
			$value = unserialize($meta_map[$object_id]);
		} else {
			$value = $meta_map[$object_id];
		}
	} else {
		$value = null;
	}

	return $value;

}


/**
 * Update metadata or create a new record
 *
 * @param string $meta_type
 * @param int    $object_id
 * @param string $meta_key
 * @param mixed  $meta_value
 *
 * @return bool
 */
function tw_metadata_update($meta_type, $object_id, $meta_key, $meta_value) {

	if (!TW_CACHE) {
		return update_metadata($meta_type, $object_id, $meta_key, $meta_value);
	}

	if (!in_array($meta_type, ['post', 'term', 'user', 'comment'])) {
		return false;
	}

	$db = tw_app_database();

	$table = $meta_type . 'meta';
	$column = $meta_type . '_id';
	$object_id = absint($object_id);
	$meta_key = stripslashes((string) $meta_key);

	$current_value = tw_metadata_get($meta_type, $object_id, $meta_key);

	if (is_array($meta_value) or is_object($meta_value)) {
		$updated_value = serialize($meta_value);
	} else {
		$updated_value = stripslashes((string) $meta_value);
	}

	if ($current_value === null) {

		$column_id = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';
		$existing_meta = [];

		$rows = $db->get_results($db->prepare("SELECT {$column_id}, meta_value FROM {$db->prefix}{$table} WHERE meta_key = %s AND $column = %d ORDER BY {$column_id} ASC", $meta_key, $object_id), ARRAY_A);

		if ($rows) {
			foreach ($rows as $row) {
				$existing_meta[(int) $row[$column_id]] = (string) $row['meta_value'];
			}
		}

		if ($existing_meta) {

			$meta_id = array_search($updated_value, $existing_meta);

			if ($meta_id !== false) {
				$current_value = $existing_meta[$meta_id];
				unset($existing_meta[$meta_id]);
			} else {
				$current_value = array_shift($existing_meta);
			}

		}

		if ($existing_meta) {
			$db->query("DELETE FROM {$db->prefix}{$table} WHERE {$column_id} IN (" . implode(', ', array_keys($existing_meta)) . ")");
		}

	} elseif (is_array($current_value) or is_object($current_value)) {
		$current_value = serialize($current_value);
	} else {
		$current_value = stripslashes((string) $current_value);
	}

	if ($current_value === $updated_value) {
		return false;
	}

	if ($current_value === null) {
		$result = $db->insert($db->$table, [
			$column => $object_id,
			'meta_key' => $meta_key,
			'meta_value' => $updated_value,
		]);
	} else {
		$result = $db->update($db->$table, [
			'meta_value' => $updated_value,
		], [
			$column => $object_id,
			'meta_key' => $meta_key,
		]);
	}

	if ($result) {
		tw_metadata_cache_update($meta_type, $object_id, $meta_key, $updated_value);
		wp_cache_delete($object_id, $meta_type . '_meta');
	}

	return (bool) $result;

}


/**
 * Delete metadata
 *
 * @param string $meta_type
 * @param int    $object_id
 * @param string $meta_key
 *
 * @return bool
 */
function tw_metadata_delete($meta_type, $object_id, $meta_key) {

	if (!TW_CACHE) {
		return delete_metadata($meta_type, $object_id, $meta_key);
	}

	if (empty($meta_type) or empty($meta_key) or !is_numeric($object_id)) {
		return false;
	}

	$meta_key = stripslashes((string) $meta_key);
	$current_value = tw_metadata_get($meta_type, $object_id, $meta_key);

	if ($current_value === null) {
		return false;
	}

	$db = tw_app_database();

	$table = $meta_type . 'meta';
	$column = sanitize_key($meta_type . '_id');
	$object_id = absint($object_id);

	if (!property_exists($db, $table)) {
		return false;
	}

	$result = $db->delete($db->$table, [
		$column => $object_id,
		'meta_key' => $meta_key,
	]);

	if ($result) {
		tw_metadata_cache_delete($meta_type, $object_id, $meta_key);
		wp_cache_delete($object_id, $meta_type . '_meta');
	}

	return (bool) $result;

}


/**
 * Get a cache key considering chunks
 *
 * @param string $meta_type
 * @param int    $object_id
 * @param string $meta_key
 *
 * @return string
 */
function tw_metadata_cache_key($meta_type, $object_id, $meta_key) {

	$cache_key = $meta_key;
	$cache_group = 'twee_meta_' . $meta_type;

	$chunk_map = wp_cache_get($cache_key . '_chunks', $cache_group);

	if (is_array($chunk_map) and $chunk_map) {

		$chunk_index = 0;
		$low_index = 0;
		$high_index = count($chunk_map) - 1;

		/**
		 * Find the first key with a value less than or equal to $object_id
		 */
		while ($low_index <= $high_index) {

			$middle_index = intdiv(($low_index + $high_index), 2);

			if ($chunk_map[$middle_index] == $object_id) {
				$chunk_index = $middle_index;
				break;
			}

			if ($chunk_map[$middle_index] > $object_id) {
				$chunk_index = $middle_index;
				$low_index = $middle_index + 1;
			} else {
				$high_index = $middle_index - 1;
			}

		}

		$cache_key .= '_chunk_' . $chunk_index;

	}

	return $cache_key;

}


/**
 * Update metadata in caches
 *
 * @param string $meta_type
 * @param int    $object_id
 * @param string $meta_key
 * @param mixed  $meta_value
 *
 * @return void
 */
function tw_metadata_cache_update($meta_type, $object_id, $meta_key, $meta_value) {

	$cache_key = tw_metadata_cache_key($meta_type, $object_id, $meta_key);
	$cache_group = 'twee_meta_' . $meta_type;

	$meta_map = wp_cache_get($cache_key, $cache_group);

	if (is_array($meta_map)) {
		$meta_map[$object_id] = (is_object($meta_value) or is_array($meta_value)) ? serialize($meta_value) : $meta_value;
		wp_cache_set($cache_key, $meta_map, $cache_group);
	}

}


/**
 * Delete metadata in cache
 *
 * @param string $meta_type
 * @param int    $object_id
 * @param string $meta_key
 *
 * @return void
 */
function tw_metadata_cache_delete($meta_type, $object_id, $meta_key) {

	$cache_key = tw_metadata_cache_key($meta_type, $object_id, $meta_key);
	$cache_group = 'twee_meta_' . $meta_type;

	$meta_map = wp_cache_get($cache_key, $cache_group);

	if (is_array($meta_map)) {
		unset($meta_map[$object_id]);
		wp_cache_set($cache_key, $meta_map, $cache_group);
	}

}


/**
 * Clear meta caches
 */
foreach (['post', 'term', 'user', 'comment'] as $meta_type) {

	add_action('added_' . $meta_type . '_meta', function($meta_id, $object_id, $meta_key, $meta_value) use ($meta_type) {
		tw_metadata_cache_update($meta_type, $object_id, $meta_key, $meta_value);
	}, 10, 4);

	add_action('updated_' . $meta_type . '_meta', function($meta_id, $object_id, $meta_key, $meta_value) use ($meta_type) {
		tw_metadata_cache_update($meta_type, $object_id, $meta_key, $meta_value);
	}, 10, 4);

	add_action('deleted_' . $meta_type . '_meta', function($meta_id, $object_id, $meta_key) use ($meta_type) {
		tw_metadata_cache_delete($meta_type, $object_id, $meta_key);
	}, 10, 3);

	add_action('deleted_' . $meta_type, function() use ($meta_type) {
		tw_app_clear('twee_meta_' . $meta_type);
	}, 20);

}