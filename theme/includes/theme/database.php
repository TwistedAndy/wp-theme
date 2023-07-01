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
function tw_database_metadata($type = 'post', $keys = '_thumbnail_id') {

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
 * Get an array with post terms
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_database_post_terms($taxonomy) {

	$cache_key = 'post_terms_' . $taxonomy;
	$cache_group = 'twee_post_terms_' . $taxonomy;

	$terms = tw_app_get($cache_key . $cache_group);

	if (is_array($terms)) {
		return $terms;
	}

	$terms = wp_cache_get($cache_key, $cache_group);

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

		tw_app_set($cache_key . $cache_group, $terms);

		wp_cache_set($cache_key, $terms, $cache_group);

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
	$cache_group = 'twee_posts_' . $type;

	if ($value) {
		if (is_string($value)) {
			$cache_key .= '_' . $value;
		} elseif (is_array($value)) {
			$cache_key .= '_' . implode('_', $value);
		}
	}

	$posts = tw_app_get($cache_key . $cache_group);

	if (is_array($posts)) {
		return $posts;
	}

	$posts = wp_cache_get($cache_key, $cache_group);

	if (!is_array($posts)) {

		$posts = [];

		$db = tw_app_database();

		$rows = $db->get_results("SELECT p.* FROM {$db->posts} p WHERE p.post_type = '{$type}'", ARRAY_A);

		if ($rows) {

			foreach ($rows as $row) {

				if (is_array($value)) {

					$data = [];

					foreach ($value as $field) {
						if (isset($row[$field])) {
							$data[$field] = $row[$field];
						}
					}

					$posts[$row[$key]] = $data;

				} elseif ($value) {

					$posts[$row[$key]] = $row[$value];

				} else {

					$posts[$row[$key]] = $row;

				}

			}

		}

		tw_app_set($cache_key . $cache_group, $posts);

		wp_cache_set($cache_key, $posts, $cache_group);

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
	$cache_group = 'twee_terms';

	if ($taxonomy) {
		$cache_key .= '_' . $taxonomy;
		$cache_group .= '_' . $taxonomy;
	}

	$terms = tw_app_get($cache_key . $cache_group);

	if (is_array($terms)) {
		return $terms;
	}

	$terms = wp_cache_get($cache_key, $cache_group);

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

					if ($field == 'term_id') {
						$terms[] = (int) $term['term_id'];
					} elseif ($field == 'all') {
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

					if ($field == 'term_id') {
						$terms[$term['taxonomy']][] = (int) $term['term_id'];
					} elseif ($field == 'all') {
						$terms[$term['taxonomy']][$term['term_id']] = $term;
					} elseif (isset($term[$field])) {
						$terms[$term['taxonomy']][$term['term_id']] = $term[$field];
					}

				}

			}

		}

		tw_app_set($cache_key . $cache_group, $terms);

		wp_cache_set($cache_key, $terms, $cache_group);

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
	$cache_group = 'twee_terms';

	if ($taxonomy) {
		$cache_key .= '_' . $taxonomy;
		$cache_group .= '_' . $taxonomy;
	}

	$labels = tw_app_get($cache_key . $cache_group);

	if (is_array($labels)) {
		return $labels;
	}

	$labels = wp_cache_get($cache_key, $cache_group);

	if (!is_array($labels)) {

		$labels = [];

		$db = tw_app_database();

		$query = "SELECT * FROM {$db->terms} t";

		if ($taxonomy or in_array($field, ['all', 'description', 'parent', 'taxonomy', 'count'])) {

			$query .= " LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id";

			if ($taxonomy) {
				$query .= " WHERE tt.taxonomy = '{$taxonomy}'";
			}

		}

		$result = $db->get_results($query, ARRAY_A);

		if ($result) {
			if (empty($field) or $field == 'all') {
				foreach ($result as $term) {
					if (!empty($term[$key])) {
						$labels[$term[$key]] = $term;
					}
				}
			} else {
				foreach ($result as $term) {
					if (!empty($term[$key])) {
						$labels[$term[$key]] = $term[$field];
					}
				}
			}
		}

		tw_app_set($cache_key . $cache_group, $labels);

		wp_cache_set($cache_key, $labels, $cache_group);

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
	$cache_group = 'twee_terms';

	$order = tw_app_get($cache_key . $cache_group);

	if (is_array($order)) {
		return $order;
	}

	$order = wp_cache_get($cache_key, $cache_group);

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

					$order[$term[$field]] = (int) $term['meta_value'];

				}

			}

		}

		asort($order);

		tw_app_set($cache_key . $cache_group, $order);

		wp_cache_set($cache_key, $order, $cache_group);

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
	$cache_group = 'twee_terms';

	if ($taxonomy and taxonomy_exists($taxonomy)) {
		$cache_key .= '_' . $taxonomy;
		$cache_group .= '_' . $taxonomy;
	} else {
		$taxonomy = false;
	}

	$terms = tw_app_get($cache_key . $cache_group);

	if (is_array($terms)) {
		return $terms;
	}

	$terms = wp_cache_get($cache_key, $cache_group);

	if (!is_array($terms)) {

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

		tw_app_set($cache_key . $cache_group, $terms);

		wp_cache_set($cache_key, $terms, $cache_group);

	}

	return $terms;

}


/**
 * Get an array with child terms
 *
 * @param int    $term_id
 * @param string $taxonomy
 * @param array  $parents
 *
 * @return array
 */
function tw_database_term_children($term_id = 0, $taxonomy = '', $parents = []) {

	$children = [];

	if ($term_id === 0) {

		$cache_key = 'terms_children';
		$cache_group = 'twee_terms';

		$children = tw_app_get($cache_key . $cache_group);

		if (is_array($children)) {
			return $children;
		}

		$children = wp_cache_get($cache_key, $cache_group);

		if (!is_array($children)) {

			$children = [];

			$parents = tw_database_term_parents($taxonomy);

			foreach ($parents as $child_id => $parent_id) {
				$children[$child_id] = tw_database_term_children($child_id, $taxonomy, $parents);
			}

			tw_app_set($cache_key . $cache_group, $children);

			wp_cache_set($cache_key, $children, $cache_group);

		}

	} else {

		unset($parents[$term_id]);

		$keys = array_keys($parents, $term_id);

		if ($keys) {

			$children = $keys;

			foreach ($children as $child_id) {

				unset($parents[$child_id]);

				$keys = tw_database_term_children($child_id, $taxonomy, $parents);

				if ($keys) {
					$children = array_merge($children, $keys);
				}

			}

		}

	}

	return $children;

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

	if (empty($term_id)) {
		return $thread;
	}

	$cache_key = 'terms_thread_' . $term_id;

	$result = tw_app_get($cache_key);

	if (is_array($result)) {
		return $result;
	}

	if (empty($thread)) {
		$thread[] = $term_id;
	}

	$parents = tw_database_term_parents();

	if (!empty($parents[$term_id])) {

		$parent_id = $parents[$term_id];

		$thread[] = $parent_id;

		$thread = tw_database_term_thread($parent_id, $thread);

	}

	tw_app_set($cache_key, $thread);

	return $thread;

}


/**
 * Get the term tree with hierarchy information
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_database_term_tree($taxonomy, $flatten = false) {

	$cache_key = 'term_hierarchy_' . $taxonomy;
	$cache_group = 'twee_terms_' . $taxonomy;

	$elements = tw_app_get($cache_key . $cache_group);

	if (!is_array($elements)) {
		$elements = wp_cache_get($cache_key, $cache_group);
	}

	if (!is_array($elements)) {

		$object = get_taxonomy($taxonomy);

		if (!($object instanceof WP_Taxonomy)) {
			return [];
		}

		$elements = [];

		$labels = tw_database_term_labels('term_id', 'name', $taxonomy);

		if ($labels) {

			if ($object->hierarchical) {

				$parents = tw_database_term_parents($taxonomy);

				foreach ($labels as $term_id => $label) {

					$term_id = (int) $term_id;

					if (!empty($parents[$term_id])) {
						$parent = (int) $parents[$term_id];
					} else {
						$parent = 0;
					}

					$elements[] = [
						'id' => $term_id,
						'name' => $label,
						'parent' => $parent,
						'children' => [],
						'depth' => 0
					];

				}

				$elements = tw_database_build_tree($elements, 0, 0);

			} else {

				foreach ($labels as $term_id => $label) {
					$elements[] = [
						'id' => (int) $term_id,
						'name' => $label,
						'parent' => 0,
						'children' => [],
						'depth' => 0
					];
				}

				usort($elements, function($a, $b) {
					return strcmp($a['name'], $b['name']);
				});

			}

		}

		tw_app_set($cache_key . $cache_group, $elements);

		wp_cache_set($cache_key, $elements, $cache_group);

	}

	if ($flatten) {

		$cache_key .= '_flatten';

		$data = tw_app_get($cache_key . $cache_group);

		if (!is_array($data)) {
			$data = wp_cache_get($cache_key);
		}

		if (!is_array($data)) {

			$elements = tw_database_flatten_tree($elements);

			tw_app_set($cache_key . $cache_group, $elements);

			wp_cache_set($cache_key, $elements, $cache_group);

		} else {

			$elements = $data;

		}

	}

	return $elements;

}


/**
 * Build a tree
 *
 * @param array $elements
 * @param int   $parent_id
 * @param int   $depth
 *
 * @return array
 */
function tw_database_build_tree($elements, $parent_id = 0, $depth = 0) {

	$branch = [];

	uasort($elements, function($a, $b) {
		return strcmp($a['name'], $b['name']);
	});

	foreach ($elements as $index => $element) {

		if ($element['parent'] == $parent_id) {

			$element['depth'] = $depth;

			$children = tw_database_build_tree($elements, $element['id'], $depth + 1);

			if ($children) {

				$element['children'] = $children;

			}

			$branch[$element['id']] = $element;

			unset($elements[$index]);

		}

	}

	return $branch;

}


/**
 * Flatten a tree to a list
 *
 * @param array $tree
 *
 * @return array
 */
function tw_database_flatten_tree($tree) {

	$list = [];

	foreach ($tree as $element) {

		$list[] = $element;

		if (!empty($element['children'])) {
			$list = array_merge($list, tw_database_flatten_tree($element['children']));
		}

		if (isset($element['children'])) {
			unset($element['children']);
		}

	}

	return $list;

}


/**
 * Clean term caches
 */
add_action('edit_terms', function($ids, $taxonomy) {
	wp_cache_flush_group('twee_terms');
	wp_cache_flush_group('twee_terms_' . $taxonomy);
}, 10, 2);

add_action('clean_term_cache', function($ids, $taxonomy) {
	wp_cache_flush_group('twee_terms');
	wp_cache_flush_group('twee_terms_' . $taxonomy);
}, 10, 2);

add_action('clean_taxonomy_cache', function($taxonomy) {
	wp_cache_flush_group('twee_terms');
	wp_cache_flush_group('twee_terms_' . $taxonomy);
}, 10, 1);


/**
 * Clear the post data caches
 */
add_action('save_post', function($post_id, $post) {
	if ($post instanceof WP_Post) {
		wp_cache_flush_group('twee_posts_' . $post->post_type);
	}
}, 10, 2);


/**
 * Clear post terms cache
 */
add_action('set_object_terms', function($object_id, $terms, $ids, $taxonomy) {
	wp_cache_flush_group('twee_post_terms_' . $taxonomy);
}, 10, 4);


/**
 * Clear meta caches
 */
foreach (['post', 'term', 'post', 'comment'] as $type) {

	add_action('added_' . $type . '_meta', function($meta_id, $object_id, $meta_key) use ($type) {
		tw_database_clean_meta($type, $meta_key);
	}, 10, 3);

	add_action('updated_' . $type . '_meta', function($meta_id, $object_id, $meta_key) use ($type) {
		tw_database_clean_meta($type, $meta_key);
	}, 10, 3);

	add_action('deleted_' . $type . '_meta', function($meta_id, $object_id, $meta_key) use ($type) {
		tw_database_clean_meta($type, $meta_key);
	}, 10, 3);

}


/**
 * Clean only cached meta keys
 *
 * @param string $type
 * @param string $key
 *
 * @return void
 */
function tw_database_clean_meta($type, $key) {

	$cache_group = 'twee_' . $type . '_meta';
	$cached_keys = wp_cache_get('meta_keys', $cache_group);

	if (!is_array($cached_keys)) {
		$cached_keys = [];
	}

	if (in_array($key, $cached_keys)) {
		wp_cache_flush_group($cache_group);
	}

}