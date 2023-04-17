<?php
/**
 * Database Processing Function
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
function tw_database_metadata($type = 'post', $keys = ['_thumbnail_id']) {

	$cache_key = $type . '_meta';

	$where = '';

	if ($keys) {

		if (!is_array($keys)) {
			$keys = explode(',', $keys);
			$keys = array_map('trim', $keys);
		}

		sort($keys);

		$cache_key .= '_' . implode('_', $keys);

		$where = " WHERE meta.meta_key IN ('" . implode("', '", $keys) . "')";

	}

	$meta = tw_app_get($cache_key);

	if (!is_array($meta)) {

		$meta = [];

		$db = tw_app_database();

		$table = $db->postmeta;
		$key = 'post_id';

		if ($type == 'term') {
			$table = $db->termmeta;
			$key = 'term_id';
		} else if ($type == 'user') {
			$table = $db->usermeta;
			$key = 'user_id';
		} else if ($type == 'comment') {
			$table = $db->commentmeta;
			$key = 'comment_id';
		}

		$result = $db->get_results("SELECT meta.{$key}, meta.meta_key, meta.meta_value FROM {$table} AS meta {$where}", ARRAY_A);

		if ($result) {

			foreach ($result as $row) {

				if (empty($row[$key]) or empty($row['meta_key'])) {
					continue;
				}

				if (empty($meta[$row[$key]])) {
					$meta[$row[$key]] = [];
				}

				$meta[$row[$key]][$row['meta_key']] = maybe_unserialize($row['meta_value']);

			}

		}

		tw_app_set($cache_key, $meta);

	}

	return $meta;

}


/**
 * Get an array with post terms
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_database_post_terms($taxonomy) {

	$cache_key = 'post_terms_' . $taxonomy;

	$terms = tw_app_get($cache_key);

	if (!is_array($terms)) {

		$terms = [];

		$db = tw_app_database();

		$rows = $db->get_results("
			SELECT tr.object_id, tt.term_id
			FROM {$db->term_relationships} tr 
			LEFT JOIN {$db->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
			WHERE tt.taxonomy = '{$taxonomy}'", ARRAY_A);

		if ($rows) {

			foreach ($rows as $row) {

				if (empty($row['object_id']) or empty($row['term_id'])) {
					continue;
				}

				if (!isset($terms[$row['object_id']])) {
					$terms[$row['object_id']] = [];
				}

				$terms[$row['object_id']][] = intval($row['term_id']);

			}

		}

		tw_app_set($cache_key, $terms);

	}

	return $terms;

}


/**
 * Get an array with post data
 *
 * @param string $type
 * @param string $key
 * @param string $value
 *
 * @return array
 */
function tw_database_post_data($type, $key = 'ID', $value = 'post_title') {

	$cache_key = 'posts_' . $type . '_' . $key;

	if ($value) {

		if (is_string($value)) {
			$cache_key .= '_' . $value;
		} elseif (is_array($value)) {
			$cache_key .= '_' . implode('_', $value);
		}

	}

	$posts = tw_app_get($cache_key);

	if (!is_array($posts)) {

		$posts = [];

		$db = tw_app_database();

		$rows = $db->get_results("
		SELECT p.*
		FROM {$db->posts} p 
		WHERE p.post_type = '{$type}'", ARRAY_A);

		if ($rows) {

			foreach ($rows as $row) {

				if (empty($row[$key])) {
					continue;
				}

				if (is_array($value)) {

					$data = [];

					foreach ($value as $field) {
						if (isset($row[$field])) {
							$data[$field] = $row[$field];
						}
					}

					$posts[$row[$key]] = $data;

				} elseif ($value and isset($row[$value])) {

					$posts[$row[$key]] = $row[$value];

				} else {

					$posts[$row[$key]] = $row;

				}

			}

		}

		tw_app_set($cache_key, $posts);

	}

	return $posts;

}


/**
 * Get all terms as array of Term IDs ($field) grouped by taxonomy
 *
 * @param string $taxonomy
 * @param string $field
 *
 * @return array
 */
function tw_database_term_taxonomies($taxonomy = '', $field = 'term_id') {

	$cache_key = 'term_taxonomies_' . $field;

	if ($taxonomy) {
		$cache_key .= '_' . $taxonomy;
	}

	$terms = tw_app_get($cache_key);

	if (!is_array($terms)) {

		$terms = [];

		$db = tw_app_database();

		$query = "SELECT t.term_id, t.name, t.slug, tt.taxonomy, tt.parent, tt.count FROM {$db->terms} t LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id";

		if ($taxonomy) {
			$query .= " WHERE tt.taxonomy = '" . $taxonomy . "'";
		}

		$result = $db->get_results($query, ARRAY_A);

		if ($result) {

			foreach ($result as $term) {

				if ($taxonomy) {

					if ($field == 'all') {
						$terms[$term['term_id']] = $term;
					} elseif (isset($term[$field])) {
						$terms[$term['term_id']] = $term[$field];
					}

				} else {

					if (empty($term['taxonomy'])) {
						continue;
					}

					if (empty($terms[$term['taxonomy']])) {
						$terms[$term['taxonomy']] = [];
					}

					if ($field == 'all') {
						$terms[$term['taxonomy']][$term['term_id']] = $term;
					} elseif (isset($term[$field])) {
						$terms[$term['taxonomy']][$term['term_id']] = $term[$field];
					}

				}

			}

		}

		tw_app_set($cache_key, $terms);

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
function tw_database_term_labels($key = 'term_id', $field = 'name', $taxonomy = '') {

	$cache_key = 'term_labels_' . $key . '_' . $field;

	if ($taxonomy) {
		$cache_key .= '_' . $taxonomy;
	}

	$labels = tw_app_get($cache_key);

	if (!is_array($labels)) {

		$labels = [];

		$db = tw_app_database();

		$query = "SELECT t.* FROM {$db->terms} t";

		if ($taxonomy and taxonomy_exists($taxonomy)) {
			$query .= " LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.taxonomy = '{$taxonomy}'";
		}

		$result = $db->get_results($query, ARRAY_A);

		if ($result) {
			foreach ($result as $term) {
				if (!empty($term[$key])) {
					if ($field and isset($term[$field])) {
						$labels[$term[$key]] = $term[$field];
					} else {
						$labels[$term[$key]] = $term;
					}

				}
			}
		}

		tw_app_set($cache_key, $labels);

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

	$order = tw_app_get($cache_key);

	if (!is_array($order)) {

		$order = [];

		$db = tw_app_database();

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

		tw_app_set($cache_key, $order);

	}

	return $order;

}


/**
 * Get an array with parent terms
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_database_term_parents($taxonomy = '') {

	$cache_key = 'terms_parents';

	if ($taxonomy and taxonomy_exists($taxonomy)) {
		$cache_key .= '_' . $taxonomy;
	} else {
		$taxonomy = false;
	}

	$terms = tw_app_get($cache_key);

	if (empty($terms)) {

		$terms = [];

		$db = tw_app_database();

		$query = "SELECT tt.term_id, tt.parent FROM {$db->term_taxonomy} tt";

		if ($taxonomy) {
			$query .= " WHERE tt.taxonomy = '{$taxonomy}'";
		}

		$rows = $db->get_results($query, ARRAY_A);

		if ($rows) {
			foreach ($rows as $row) {
				if (!empty($row['term_id']) and isset($row['parent'])) {
					$terms[intval($row['term_id'])] = intval($row['parent']);
				}
			}
		}

		tw_app_set($cache_key, $terms);

	}

	return $terms;

}


/**
 * Get an array with a term and all parents
 *
 * @param int   $term_id
 * @param array $thread
 *
 * @return array|mixed
 */
function tw_database_term_thread($term_id, $thread = []) {

	if ($term_id > 0) {

		if (empty($thread)) {
			$thread[] = $term_id;
		}

		$parents = tw_database_term_parents();

		if (!empty($parents[$term_id])) {

			$parent_id = $parents[$term_id];

			$thread[] = $parent_id;

			$thread = tw_database_term_thread($parent_id, $thread);

		}

	}

	return $thread;

}