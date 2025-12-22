<?php
/**
 * Terms Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */

/**
 * Get term data as an array
 *
 * @param string $key
 * @param string $field
 * @param string $taxonomy
 *
 * @return array
 */
function tw_term_data($key = 'term_id', $field = 'name', $taxonomy = '') {

	$cache_key = 'term_labels_' . $key . '_' . $field;
	$cache_group = 'twee_terms';

	if ($taxonomy) {
		$cache_group .= '_' . $taxonomy;
	}

	$data = wp_cache_get($cache_key, $cache_group);

	if (is_array($data)) {
		return $data;
	}

	$data = [];

	$db = tw_app_database();

	$query = "SELECT * FROM {$db->terms} t";

	if ($taxonomy or in_array($field, ['all', 'description', 'parent', 'taxonomy', 'count'])) {

		$query .= " LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id";

		if ($taxonomy) {
			$query .= $db->prepare(" WHERE tt.taxonomy = %s", $taxonomy);
		}

	}

	$result = $db->get_results($query, ARRAY_A);

	if ($result) {
		if (empty($field) or $field == 'all') {
			foreach ($result as $term) {
				if (!empty($term[$key])) {
					$data[$term[$key]] = $term;
				}
			}
		} else {
			foreach ($result as $term) {
				if (!empty($term[$key])) {
					$data[$term[$key]] = $term[$field];
				}
			}
		}
	}

	wp_cache_set($cache_key, $data, $cache_group);

	return $data;

}


/**
 * Get terms grouped by taxonomies
 *
 * @param string $taxonomy
 * @param string $field
 *
 * @return array
 */
function tw_term_taxonomies($taxonomy = '', $field = 'term_id') {

	$cache_key = 'term_taxonomies_' . $field;
	$cache_group = 'twee_terms';

	if ($taxonomy) {
		$cache_group .= '_' . $taxonomy;
	}

	$terms = wp_cache_get($cache_key, $cache_group);

	if (is_array($terms)) {
		return $terms;
	}

	$terms = [];

	$db = tw_app_database();

	$query = "SELECT t.term_id, t.name, t.slug, tt.taxonomy, tt.parent, tt.count FROM {$db->terms} t LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id";

	if ($taxonomy) {
		$query .= $db->prepare(" WHERE tt.taxonomy = %s", $taxonomy);
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

	wp_cache_set($cache_key, $terms, $cache_group);

	return $terms;

}


/**
 * Get post terms as links
 *
 * @param int|WP_Post $post_id   Post ID or WP_Post object
 * @param string      $taxonomy  Term taxonomy
 * @param string      $class     Link class
 * @param bool        $with_link Wrap term a link
 *
 * @return array
 */
function tw_term_links($post_id, $taxonomy = 'category', $class = 'category', $with_link = true) {

	if ($post_id instanceof WP_Post) {
		$post_id = $post_id->ID;
	}

	$result = [];

	if (!is_numeric($post_id)) {
		return $result;
	}

	$map = tw_post_terms($taxonomy);

	if (!empty($map[$post_id]) and is_array($map[$post_id])) {

		if ($class) {
			$class = ' class="' . esc_attr($class) . '"';
		}

		$labels = tw_term_data('term_id', 'name', $taxonomy);

		foreach ($map[$post_id] as $term_id) {

			if (empty($labels[$term_id])) {
				continue;
			}

			if ($with_link) {
				$result[] = '<a href="' . tw_term_link($term_id, $taxonomy) . '"' . $class . '>' . $labels[$term_id] . '</a>';
			} else {
				if ($class) {
					$result[] = '<span' . $class . '>' . $labels[$term_id] . '</span>';
				} else {
					$result[] = $labels[$term_id];
				}
			}
		}

	}

	return $result;

}


/**
 * Get a term link
 *
 * @param int    $term_id
 * @param string $taxonomy
 *
 * @return string
 */
function tw_term_link($term_id, $taxonomy) {

	global $wp_rewrite;

	$cache_key = 'link_' . $term_id;
	$cache_group = 'twee_terms_' . $taxonomy;

	$link = wp_cache_get($cache_key, $cache_group);

	if (is_string($link)) {
		return $link;
	}

	$object = get_taxonomy($taxonomy);

	if ($object instanceof WP_Taxonomy and $object->public and $object->rewrite) {

		$slugs = tw_term_data('term_id', 'slug', $taxonomy);

		if (empty($slugs[$term_id])) {

			$link = '';

		} else {

			$link = $wp_rewrite->get_extra_permastruct($taxonomy);

			$slug = $slugs[$term_id];

			if (empty($link)) {

				if ('category' === $taxonomy) {
					$link = '?cat=' . $term_id;
				} else {
					$link = "?taxonomy=$taxonomy&term=$slug";
				}

				$link = home_url($link);

			} else {

				if ($object->rewrite['hierarchical']) {

					$list = [];

					$parents = tw_term_ancestors($term_id, $taxonomy);

					if ($parents) {

						$parents = array_reverse($parents);

						$parents[] = $term_id;

						foreach ($parents as $parent) {
							if (!empty($slugs[$parent])) {
								$list[] = $slugs[$parent];
							}
						}

					} else {

						$list[] = $slug;

					}

					$link = str_replace("%$taxonomy%", implode('/', $list), $link);

				} else {

					$link = str_replace("%$taxonomy%", $slug, $link);

				}

				$link = home_url(user_trailingslashit($link, 'category'));

			}

		}

	}

	wp_cache_set($cache_key, $link, $cache_group);

	return $link;

}


/**
 * Get an array with parent terms
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_term_parents($taxonomy) {

	$cache_key = 'terms_parents';
	$cache_group = 'twee_terms_' . $taxonomy;

	$parents = wp_cache_get($cache_key, $cache_group);

	if (is_array($parents)) {
		return $parents;
	}

	$parents = [];

	$db = tw_app_database();

	$query = $db->prepare("SELECT tt.term_id, tt.parent FROM {$db->term_taxonomy} tt WHERE tt.taxonomy = %s", $taxonomy);

	$rows = $db->get_results($query, ARRAY_A);

	if ($rows) {
		foreach ($rows as $row) {
			if (!empty($row['term_id']) and isset($row['parent'])) {
				$parents[(int) $row['term_id']] = (int) $row['parent'];
			}
		}
	}

	wp_cache_set($cache_key, $parents, $cache_group);

	return $parents;

}


/**
 * Get an array with a term and all parents
 *
 * @param int    $term_id
 * @param string $taxonomy
 *
 * @return array
 */
function tw_term_ancestors($term_id, $taxonomy) {

	$cache_key = 'terms_ancestors_' . $term_id;
	$cache_group = 'twee_terms_' . $taxonomy;

	$ancestors = wp_cache_get($cache_key, $cache_group);

	if (is_array($ancestors)) {
		return $ancestors;
	}

	$parents = tw_term_parents($taxonomy);

	$thread = tw_term_ancestors_walker($term_id, [], $parents);

	wp_cache_set($cache_key, $thread, $cache_group);

	return $thread;

}


/**
 * Get the parent term ID from an array
 *
 * @param int   $term_id
 * @param array $thread
 * @param array $parents
 *
 * @return array
 */
function tw_term_ancestors_walker($term_id, $thread, $parents) {

	if (!empty($parents[$term_id])) {
		$parent_id = (int) $parents[$term_id];
		$thread[] = $parent_id;
		$thread = tw_term_ancestors_walker($parent_id, $thread, $parents);
	}

	return $thread;

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
function tw_term_children($term_id = 0, $taxonomy = '', $parents = []) {

	$children = [];

	if ($term_id === 0) {

		$cache_key = 'terms_children';
		$cache_group = 'twee_terms';

		if ($taxonomy) {
			$cache_group .= '_' . $taxonomy;
		}

		$children = wp_cache_get($cache_key, $cache_group);

		if (is_array($children)) {
			return $children;
		}

		$children = [];

		$parents = tw_term_parents($taxonomy);

		foreach ($parents as $child_id => $parent_id) {
			$children[$child_id] = tw_term_children($child_id, $taxonomy, $parents);
		}

		wp_cache_set($cache_key, $children, $cache_group);

	} else {

		unset($parents[$term_id]);

		$keys = array_keys($parents, $term_id);

		if ($keys) {

			$children = $keys;

			foreach ($children as $child_id) {

				unset($parents[$child_id]);

				$keys = tw_term_children($child_id, $taxonomy, $parents);

				if ($keys) {
					$children = array_merge($children, $keys);
				}

			}

		}

	}

	return $children;

}


/**
 * Get terms ordered by a meta key
 *
 * @param string $field
 * @param string $taxonomy
 * @param string $key
 *
 * @return array
 */
function tw_term_order($field = 'term_id', $taxonomy = '', $key = 'order') {

	$cache_key = 'terms_order_' . $field;
	$cache_group = 'twee_terms';

	if ($taxonomy) {
		$cache_key .= '_' . $taxonomy;
	}

	$order = wp_cache_get($cache_key, $cache_group);

	if (is_array($order)) {
		return $order;
	}

	$order = [];

	$db = tw_app_database();

	if ($taxonomy and is_string($taxonomy)) {
		$where = " WHERE tt.taxonomy = '" . esc_sql($taxonomy) . "'";
	} else {
		$where = '';
	}

	if ($key and is_string($key)) {
		$key = esc_sql($key);
	} else {
		$key = 'order';
	}

	$result = $db->get_results("SELECT t.term_id, t.slug, t.name, tm.meta_value FROM {$db->terms} t LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id LEFT JOIN {$db->termmeta} tm ON t.term_id = tm.term_id AND tm.meta_key = '{$key}'{$where}", ARRAY_A);

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

	wp_cache_set($cache_key, $order, $cache_group);

	return $order;

}


/**
 * Get an array with posts for each term
 *
 * @param string $taxonomy
 * @param string $type
 * @param string $status
 * @param bool   $children
 *
 * @return array
 */
function tw_term_posts($taxonomy, $type = '', $status = '', $children = true) {

	$cache_key = 'term_posts';
	$cache_group = 'twee_post_terms_' . $taxonomy;

	if ($type and is_string($type)) {
		$type = trim($type);
		$cache_key .= '_' . $type;
	} else {
		$type = '';
	}

	if ($status and is_string($status)) {
		$status = trim($status);
		$cache_key .= '_' . $status;
	} else {
		$status = '';
	}

	if ($children) {

		$object = get_taxonomy($taxonomy);

		if ($object instanceof \WP_Taxonomy and empty($object->hierarchical)) {
			$children = false;
		} else {
			$cache_group .= '_children';
		}

	}

	$terms = wp_cache_get($cache_key, $cache_group);

	if (is_array($terms)) {
		return $terms;
	}

	$terms = [];

	if ($children) {

		$term_posts = tw_term_posts($taxonomy, $type, $status, false);
		$term_children = tw_term_children(0, $taxonomy);

		foreach ($term_children as $term_id => $children) {

			$collections = [(int) $term_id];

			if ($children) {
				$collections = array_merge($children, $collections);
			}

			foreach ($collections as $collection) {

				if (!empty($term_posts[$collection])) {

					$items = $term_posts[$collection];

					if (!isset($terms[$term_id])) {
						$terms[$term_id] = [];
					}

					$terms[$term_id] = array_merge($terms[$term_id], $items);

				}

			}

		}

	} else {

		$db = tw_app_database();

		$where = [];

		if ($status) {

			$status = esc_sql($status);

			if (strpos($status, ',')) {
				$status = explode(',', $status);
			} else {
				$status = [$status];
			}

			$where[] = "p.post_status IN ('" . implode(',', $status) . "')";

		}

		if ($type) {

			$type = esc_sql($type);

			if (strpos($type, ',')) {
				$type = explode(',', $status);
			} else {
				$type = [$type];
			}

			$where[] = "p.post_type IN ('" . implode(',', $type) . "')";

		}

		if ($where) {

			$where = implode(' AND ', $where);

			$rows = $db->get_results($db->prepare("
				SELECT tr.object_id, tt.term_id
				FROM {$db->term_relationships} tr 
				LEFT JOIN {$db->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
				LEFT JOIN {$db->posts} p ON p.ID = tr.object_id
				WHERE tt.taxonomy = %s AND {$where}", $taxonomy), ARRAY_A);

		} else {

			$rows = $db->get_results($db->prepare("
				SELECT tr.object_id, tt.term_id
				FROM {$db->term_relationships} tr 
				LEFT JOIN {$db->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
				WHERE tt.taxonomy = %s", $taxonomy), ARRAY_A);

		}

		foreach ($rows as $row) {

			if (empty($row['object_id']) or empty($row['term_id'])) {
				continue;
			}

			if (!isset($terms[$row['term_id']])) {
				$terms[$row['term_id']] = [];
			}

			$terms[$row['term_id']][] = (int) $row['object_id'];

		}

	}

	wp_cache_set($cache_key, $terms, $cache_group);

	return $terms;

}


/**
 * Get the term tree with hierarchy information
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_term_tree($taxonomy, $flatten = false) {

	$cache_key = 'term_hierarchy';
	$cache_group = 'twee_terms_' . $taxonomy;

	$elements = wp_cache_get($cache_key, $cache_group);

	if (!is_array($elements)) {

		$object = get_taxonomy($taxonomy);

		if (!($object instanceof WP_Taxonomy)) {
			return [];
		}

		$elements = [];

		$data = tw_term_data('term_id', 'name', $taxonomy);

		if ($data) {

			if ($object->hierarchical) {

				$parents = tw_term_parents($taxonomy);

				foreach ($data as $term_id => $label) {

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

				usort($elements, function($a, $b) {
					return strcmp($a['name'], $b['name']);
				});

				$elements = tw_term_build_tree($elements, 0, 0);

			} else {

				foreach ($data as $term_id => $label) {
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

		wp_cache_set($cache_key, $elements, $cache_group);

	}

	if ($flatten) {

		$cache_key .= '_flatten';

		$data = wp_cache_get($cache_key, $cache_group);

		if (!is_array($data)) {

			$elements = tw_term_flatten_tree($elements);

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
function tw_term_build_tree($elements, $parent_id = 0, $depth = 0) {

	$branch = [];

	foreach ($elements as $index => $element) {

		if ($element['parent'] == $parent_id) {

			$element['depth'] = $depth;

			$children = tw_term_build_tree($elements, $element['id'], $depth + 1);

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
function tw_term_flatten_tree($tree) {

	$list = [];

	foreach ($tree as $element) {

		if (!empty($element['children'])) {
			$children = $element['children'];
			unset($element['children']);
		} else {
			$children = false;
		}

		$list[] = $element;

		if ($children) {
			$list = array_merge($list, tw_term_flatten_tree($children));
		}

	}

	return $list;

}


/**
 * Clear term cache
 *
 * @param string $taxonomy
 *
 * @return void
 */
function tw_term_clear_cache($taxonomy) {
	tw_app_clear('twee_terms');
	tw_app_clear('twee_terms_' . $taxonomy);
}


/**
 * Clean term caches
 */
add_action('edited_terms', function($ids, $taxonomy) {
	tw_term_clear_cache($taxonomy);
}, 10, 2);

add_action('clean_term_cache', function($ids, $taxonomy) {
	tw_term_clear_cache($taxonomy);
}, 10, 2);

add_action('clean_taxonomy_cache', function($taxonomy) {
	tw_term_clear_cache($taxonomy);
}, 10, 1);