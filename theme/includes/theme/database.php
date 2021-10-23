<?php

/**
 * Get the database object
 *
 * @return wpdb
 */
function tw_database_object() {

	global $wpdb;

	if ($wpdb instanceof \wpdb) {

		return $wpdb;

	} else {

		$db_user = defined('DB_USER') ? DB_USER : '';
		$db_password = defined('DB_PASSWORD') ? DB_PASSWORD : '';
		$db_name = defined('DB_NAME') ? DB_NAME : '';
		$db_host = defined('DB_HOST') ? DB_HOST : '';

		return new \wpdb($db_user, $db_password, $db_name, $db_host);

	}

}


/**
 * Get all terms as array of Term IDs ($field) grouped by taxonomy
 *
 * @return array
 */
function tw_database_term_taxonomies($field = 'term_id') {

	$cache_key = 'term_taxonomies_' . $field;

	$terms = tw_cache_get($cache_key);

	if (empty($terms)) {

		$terms = [];

		$db = tw_database_object();

		$result = $db->get_results("SELECT t.term_id, t.name, t.slug, tt.taxonomy FROM {$db->terms} t LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id", ARRAY_A);

		if ($result) {

			foreach ($result as $term) {

				if (!empty($term[$field]) and !empty($term['taxonomy']) and !empty($term['term_id'])) {

					if (empty($terms[$term['taxonomy']])) {
						$terms[$term['taxonomy']] = [];
					}

					$terms[$term['taxonomy']][] = $term['term_id'];

				}

			}

		}

		tw_cache_set($cache_key, $terms);

	}

	return $terms;

}


/**
 * Get all terms as Term ID => Label array ($key => $field)
 *
 * @param string $key
 * @param string $field
 *
 * @return array
 */
function tw_database_term_labels($key = 'term_id', $field = 'name') {

	$cache_key = 'term_labels_' . $key . '_' . $field;

	$labels = tw_cache_get($cache_key);

	if (empty($labels)) {

		$labels = [];

		$db = tw_database_object();

		$result = $db->get_results("SELECT t.* FROM {$db->terms} t", ARRAY_A);

		if ($result) {
			foreach ($result as $term) {
				if (!empty($term[$key])) {
					if (isset($term[$field])) {
						$labels[$term[$key]] = $term[$field];
					} else {
						$labels[$term[$key]] = $term;
					}

				}
			}
		}

		tw_cache_set($cache_key, $labels);

	}

	return $labels;

}


/**
 * Get all WooCommerce attributes as Term ID => Label ordered array
 *
 * @param string $field
 *
 * @return array
 */
function tw_database_term_order($field = 'term_id') {

	$cache_key = 'terms_order_' . $field;

	$order = tw_cache_get($cache_key);

	if (empty($order)) {

		$order = [];

		$db = tw_database_object();

		$result = $db->get_results("SELECT t.term_id, t.slug, t.name, tm.meta_value FROM {$db->terms} t LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id LEFT JOIN {$db->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = 'order' WHERE tt.taxonomy LIKE 'pa_%'", ARRAY_A);

		if ($result) {

			foreach ($result as $term) {

				if (!empty($term[$field])) {

					if (empty($term['meta_value'])) {
						$term['meta_value'] = 0;
					}

					$order[$term[$field]] = intval($term['meta_value']);

				}

			}

		}

		asort($order);

		tw_cache_set($cache_key, $order);

	}

	return $order;

}


/**
 * Get all WooCommerce attributes as Term ID => Label ordered array
 *
 * @param array|string $keys
 *
 * @return array
 */
function tw_database_term_meta($keys = ['swap', 'options', 'image']) {

	$cache_key = 'term_meta';

	$where = '';

	if ($keys) {

		if (!is_array($keys)) {
			$keys = explode(',', $keys);
			$keys = array_map('trim', $keys);
		}

		sort($keys);

		$cache_key .= '_' . implode('_', $keys);

		$where = " WHERE tm.meta_key IN ('" . implode("', '", $keys) . "')";

	}

	$meta = tw_cache_get($cache_key);

	if (empty($meta)) {

		$meta = [];

		$db = tw_database_object();

		$result = $db->get_results("SELECT tm.term_id, tm.meta_key, tm.meta_value FROM {$db->termmeta} tm {$where}", ARRAY_A);

		if ($result) {

			foreach ($result as $row) {

				if (empty($row['term_id']) or empty($row['meta_key'])) {
					continue;
				}

				if (empty($meta[$row['term_id']])) {
					$meta[$row['term_id']] = [];
				}

				$meta[$row['term_id']][$row['meta_key']] = maybe_unserialize($row['meta_value']);

			}

		}

		tw_cache_set($cache_key, $meta);

	}

	return $meta;

}