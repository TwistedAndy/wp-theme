<?php
/**
 * Posts Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.3
 */

/**
 * Get an array with post data
 *
 * @param string                                      $type
 * @param 'ID'|'post_author'|'post_name'|'post_title' $key
 * @param string|string[]                             $fields
 * @param string                                      $status
 * @param string                                      $order
 *
 * @return array
 */
function tw_post_data(string $type, string $key = 'ID', $fields = 'post_title', string $status = '', string $order = 'p.ID ASC'): array
{
	$cache_key = 'posts_' . $key;
	$cache_group = 'twee_posts';

	if ($type) {
		$cache_group .= '_' . $type;
	}

	$select = 'p.*';

	if (is_string($fields) and strpos($fields, ',') > 0) {
		$fields = explode(',', $fields);
	}

	if (is_string($fields)) {
		if ($fields === '') {
			$select = 'p.' . $key;
		} else {
			$cache_key .= '_' . $fields;
			$select = 'p.' . $key . ', p.' . $fields;
		}
	} elseif (is_array($fields)) {
		$fields = array_map('trim', $fields);

		asort($fields);

		$cache_key .= '_' . implode('_', $fields);
		$select = 'p.' . $key . ', p.' . implode(', p.', $fields);
	}

	if ($status) {
		$cache_key .= '_' . $status;
	}

	if (is_string($order) and $order != 'p.ID ASC') {
		$cache_key .= '_' . crc32($order);
	}

	if (!is_string($order) or empty($order)) {
		$order = 'p.ID ASC';
	}

	$data = wp_cache_get($cache_key, $cache_group);

	if (is_array($data)) {
		return $data;
	}

	$data = [];

	$db = tw_app_database();

	$select = $db->_escape($select);

	$where = [];

	if ($type) {
		$where[] = "p.post_type = '" . esc_sql($type) . "'";
	}

	if ($status) {
		if (strpos($status, ',') > 0) {
			$parts = array_map('trim', explode(',', $db->_escape($status)));
			$where[] = "p.post_status IN ('" . implode("','", $parts) . "')";
		} else {
			$where[] = "p.post_status = '" . $db->_escape($status) . "'";
		}
	}

	if ($where) {
		$where = 'WHERE ' . implode(' AND ', $where);
	} else {
		$where = '';
	}

	$rows = $db->get_results("SELECT {$select} FROM {$db->posts} p " . $where . " ORDER BY " . $db->_escape($order), ARRAY_A);

	if ($rows) {
		if (is_array($fields)) {
			foreach ($rows as $row) {
				$array = [];
				foreach ($fields as $field) {
					$array[$field] = $row[$field] ?? '';
				}
				$data[$row[$key]] = $array;

			}
		} elseif (is_string($fields)) {
			if ($fields === '') {
				foreach ($rows as $row) {
					$data[] = $row[$key];
				}
			} else {
				foreach ($rows as $row) {
					$data[$row[$key]] = $row[$fields];
				}
			}
		} else {
			foreach ($rows as $row) {
				$data[$row[$key]] = $row;
			}
		}
	}

	wp_cache_set($cache_key, $data, $cache_group);

	return $data;
}


/**
 * Get an array with post terms
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_post_terms(string $taxonomy): array
{
	$cache_key = 'post_terms';
	$cache_group = 'twee_post_terms_' . $taxonomy;

	$terms = wp_cache_get($cache_key, $cache_group);

	if (is_array($terms)) {
		return $terms;
	}

	$terms = [];

	$db = tw_app_database();

	$rows = $db->get_results($db->prepare("
		SELECT tr.object_id, tt.term_id
		FROM {$db->term_relationships} tr 
		LEFT JOIN {$db->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
		WHERE tt.taxonomy = %s", $taxonomy), ARRAY_A);

	if ($rows) {
		foreach ($rows as $row) {
			if (empty($row['object_id']) or empty($row['term_id'])) {
				continue;
			}
			if (!isset($terms[$row['object_id']])) {
				$terms[(int) $row['object_id']] = [];
			}
			$terms[(int) $row['object_id']][] = (int) $row['term_id'];
		}
	}

	wp_cache_set($cache_key, $terms, $cache_group);

	return $terms;
}


/**
 * Get post terms with ancestors
 *
 * @param int    $post_id
 * @param string $taxonomy
 * @param bool   $single
 *
 * @return array
 */
function tw_post_term_thread(int $post_id, string $taxonomy, $single = true): array
{
	$cache_key = 'post_term_thread_' . $post_id;
	$cache_group = 'twee_post_terms_' . $taxonomy;

	if ($single) {
		$cache_key .= '_single';
	}

	$thread = wp_cache_get($cache_key, $cache_group);

	if (is_array($thread)) {
		return $thread;
	}

	$thread = [];
	$threads = [];

	$terms = tw_post_get_terms($post_id, $taxonomy);

	if (empty($terms)) {
		wp_cache_set($cache_key, $thread, $cache_group);

		return $thread;
	}

	foreach ($terms as $term) {

		$ancestors = tw_term_ancestors($term, $taxonomy);

		if ($ancestors) {
			$ancestors = array_reverse($ancestors);
		}

		$ancestors[] = $term;

		$threads[] = $ancestors;

	}

	$result = [];

	$labels = tw_term_data('term_id', 'name', $taxonomy);

	if ($single) {

		foreach ($threads as $data) {
			if (count($data) > count($thread)) {
				$thread = $data;
			}
		}

		if ($thread) {

			$thread = array_reverse($thread);

			foreach ($thread as $term) {
				if (!empty($labels[$term])) {
					$result[$term] = $labels[$term];
				}
			}

		}

	} else {

		foreach ($threads as $index => $thread) {

			if (empty($result[$index])) {
				$result[$index] = [];
			}

			$thread = array_reverse($thread);

			foreach ($thread as $term) {
				if (!empty($labels[$term])) {
					$result[$index][$term] = $labels[$term];
				}
			}

		}

	}

	wp_cache_set($cache_key, $result, $cache_group);

	return $result;

}


/**
 * Get a list of terms attached to a post
 */
function tw_post_get_terms(int $post_id, string $taxonomy): array
{
	/**
	 * @see get_object_term_cache()
	 */
	$term_cache = wp_cache_get($post_id, $taxonomy . '_relationships');

	if (is_array($term_cache)) {
		$term_ids = [];

		foreach ($term_cache as $term_id) {
			if (is_numeric($term_id)) {
				$term_ids[] = (int) $term_id;
			} elseif (is_object($term_id) and isset($term_id->term_id)) {
				$term_ids[] = (int) $term_id->term_id;
			}
		}

		return $term_ids;
	}

	$term_map = tw_post_terms($taxonomy);

	if (isset($term_map[$post_id]) and is_array($term_map[$post_id])) {
		$term_ids = $term_map[$post_id];
	} else {
		$term_ids = [];
	}

	wp_cache_set($post_id, $term_ids, $taxonomy . '_relationships');

	return $term_ids;
}


/**
 * Sync the post terms directly in the database
 *
 * @param int    $post_id
 * @param int[]  $term_ids
 * @param string $taxonomy
 * @param bool   $append
 *
 * @return bool True if the terms were changed
 */
function tw_post_set_terms(int $post_id, array $term_ids, string $taxonomy, bool $append = false): bool
{
	$term_map = tw_post_terms($taxonomy);

	if (isset($term_map[$post_id]) and is_array($term_map[$post_id])) {
		$old_ids = $term_map[$post_id];
	} else {
		$old_ids = [];
	}

	if ($term_ids !== []) {
		$term_ids = array_map('intval', $term_ids);
	}

	if ($append and $old_ids) {
		$new_ids = array_values(array_unique(array_merge($term_ids, $old_ids)));
	} else {
		$new_ids = $term_ids;
	}

	$count_old = count($old_ids);
	$count_new = count($new_ids);

	if ($count_old !== $count_new) {
		$update_terms = true;
	} elseif ($count_old === 0) {
		$update_terms = false;
	} else {
		sort($new_ids);
		sort($old_ids);

		$update_terms = ($new_ids !== $old_ids);
	}

	if (!$update_terms) {
		return false;
	}

	// The term IDs are used as term_taxonomy_id, as they are always equal
	$added_ids = array_diff($new_ids, $old_ids);
	$removed_ids = array_diff($old_ids, $new_ids);

	if (!$added_ids and !$removed_ids) {
		return false;
	}

	$db = tw_app_database();

	if ($removed_ids) {
		$db->query("DELETE FROM {$db->term_relationships} WHERE object_id = {$post_id} AND term_taxonomy_id IN (" . implode(',', $removed_ids) . ")");
	}

	if ($added_ids) {
		$values = [];

		foreach ($added_ids as $tt_id) {
			$values[] = "({$post_id},{$tt_id},0)";
		}

		$db->query("INSERT IGNORE INTO {$db->term_relationships} (object_id, term_taxonomy_id, term_order) VALUES " . implode(',', $values));
	}

	tw_post_recount_terms($taxonomy, array_merge($added_ids, $removed_ids), true);

	wp_cache_set('last_changed', microtime(), 'terms');
	wp_cache_set($post_id, $new_ids, $taxonomy . '_relationships');

	do_action('set_object_terms', $post_id, $term_ids, $new_ids, $taxonomy, $append, $old_ids);

	// Skip, if the terms were changed by a hook, e.g. the default product category
	if (wp_cache_get($post_id, $taxonomy . '_relationships') === $new_ids) {
		$term_map[$post_id] = $new_ids;

		$cache_key = 'post_terms';
		$cache_group = 'twee_post_terms_' . $taxonomy;

		wp_cache_set($cache_key, $term_map, $cache_group);
	}

	return true;
}


/**
 * Recount the published posts attached to the taxonomy terms
 *
 * @param string $taxonomy
 * @param int[]  $term_ids Term IDs to recount, all terms if empty
 * @param bool   $defer    Recount all terms in the background if the Action Scheduler is available
 *
 * @return void
 */
function tw_post_recount_terms(string $taxonomy, array $term_ids = [], bool $defer = false): void
{
	if ($defer and function_exists('as_schedule_single_action')) {
		$task = 'twee_post_recount_terms_event';
		$args = ['taxonomy' => $taxonomy];

		if (!tw_app_get($taxonomy, 'twee_post_recount') and as_has_scheduled_action($task, $args) === false) {
			as_schedule_single_action(time() + 90, $task, $args);
		}

		tw_app_set($taxonomy, true, 'twee_post_recount');

		return;
	}

	$object = get_taxonomy($taxonomy);

	// Use the custom callback, e.g. _wc_term_recount(), which also updates the WooCommerce term counts
	if ($object instanceof WP_Taxonomy and $object->update_count_callback and $object->update_count_callback !== '_update_post_term_count') {
		if (!$term_ids) {
			$term_ids = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'tt_ids']);
		}

		if (is_array($term_ids) and $term_ids) {
			wp_update_term_count_now($term_ids, $taxonomy);
		}

		return;
	}

	$db = tw_app_database();

	$where = $db->prepare('tt.taxonomy = %s', $taxonomy);

	// The term IDs are used as term_taxonomy_id, as they are always equal
	if ($term_ids) {
		$where .= ' AND tt.term_taxonomy_id IN (' . implode(',', array_map('intval', $term_ids)) . ')';
	}

	$join = "p.ID = tr.object_id AND p.post_status = 'publish'";

	if ($object instanceof WP_Taxonomy and $object->object_type) {
		$join .= " AND p.post_type IN ('" . implode("','", array_map('esc_sql', $object->object_type)) . "')";
	}

	// Read the actual counts in one query and update only the changed terms
	$counts = $db->get_results("
		SELECT tt.term_taxonomy_id, COUNT(p.ID) AS total
		FROM {$db->term_taxonomy} tt
		LEFT JOIN {$db->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
		LEFT JOIN {$db->posts} p ON {$join}
		WHERE {$where}
		GROUP BY tt.term_taxonomy_id, tt.count
		HAVING tt.count <> total", ARRAY_A);

	if (!$counts) {
		return;
	}

	$cases = [];

	foreach ($counts as $row) {
		$cases[(int) $row['term_taxonomy_id']] = 'WHEN ' . (int) $row['term_taxonomy_id'] . ' THEN ' . (int) $row['total'];
	}

	$db->query("UPDATE {$db->term_taxonomy} SET count = CASE term_taxonomy_id " . implode(' ', $cases) . " END WHERE term_taxonomy_id IN (" . implode(',', array_keys($cases)) . ")");

	wp_cache_delete_multiple(array_keys($cases), 'terms');
	wp_cache_set('last_changed', microtime(), 'terms');
}

add_action('twee_post_recount_terms_event', 'tw_post_recount_terms');


/**
 * Build the post query
 *
 * @param string|array $type
 * @param array        $block
 *
 * @return array
 */
function tw_post_query(string|array $type, array $block = []): array
{
	$taxonomies = get_object_taxonomies($type);

	$args = [
		'post_type'      => $type,
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'offset'         => 0
	];

	if (!empty($block['exclude'])) {
		$args['post__not_in'] = $block['exclude'];
	}

	if (!empty($block['number'])) {
		$args['posts_per_page'] = (int) $block['number'];
	}

	if (!empty($block['offset'])) {
		$args['offset'] = (int) $block['offset'];
	}

	$tax_query = [];
	$meta_query = [];

	if ($taxonomies) {
		foreach ($taxonomies as $taxonomy) {
			if (!empty($block[$taxonomy]) and is_array($block[$taxonomy])) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $block[$taxonomy],
				];
			}
		}
	}

	$order = 'date';

	if (!empty($block['order'])) {
		$order = $block['order'];
	}

	if ($order == 'custom' and !empty($block['items'])) {
		$args['post__in'] = $block['items'];
		$args['orderby'] = 'post__in';
		$args['order'] = 'ASC';
	} elseif ($order == 'related') {

		$object = get_queried_object();

		if ($object instanceof WP_Post) {

			if (!isset($args['post__not_in'])) {
				$args['post__not_in'] = [$object->ID];
			} else {
				$args['post__not_in'][] = $object->ID;
			}

			$taxonomy = reset($taxonomies);

			if ($taxonomy and empty($block[$taxonomy])) {

				$terms = tw_post_terms($taxonomy);

				if (!empty($terms[$object->ID])) {
					$tax_query[] = [
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => $terms[$object->ID],
					];
				}

			}

		}

	} else {

		$args['orderby'] = $order;

		if ($order == 'date') {
			$args['order'] = 'DESC';
		} else {
			$args['order'] = 'ASC';
		}

		if ($order == 'views') {
			$meta_query['views'] = [
				'key'     => 'views_total',
				'compare' => 'EXISTS',
				'type'    => 'NUMERIC'
			];

			$args['orderby'] = [
				'views' => 'DESC',
				'date'  => 'DESC'
			];
		}

	}

	if ($tax_query) {
		$tax_query['relation'] = 'AND';
		$args['tax_query'] = $tax_query;
	}

	if ($meta_query) {
		$args['meta_query'] = $meta_query;
	}

	return $args;
}


/**
 * Clear the post caches
 *
 * @param int     $post_id
 * @param WP_Post $post
 *
 * @return void
 */
function tw_post_clear_cache(int $post_id, WP_Post $post): void
{
	tw_app_clear('twee_posts');
	tw_app_clear('twee_posts_' . $post->post_type);
}

add_action('save_post', 'tw_post_clear_cache', 10, 2);
add_action('delete_post', 'tw_post_clear_cache', 10, 2);


/**
 * Clear post terms cache
 *
 * The taxonomy is the 3rd argument of the deleted_term_relationships action
 */
function tw_post_clear_terms(int $object_id, array $terms, array|string $ids, string $taxonomy = ''): void
{
	if (is_string($ids)) {
		$taxonomy = $ids;
	}

	tw_app_clear('twee_post_terms_' . $taxonomy);
}

add_action('set_object_terms', 'tw_post_clear_terms', 10, 4);
add_action('deleted_term_relationships', 'tw_post_clear_terms', 10, 3);


/**
 * Disable the legacy WooCommerce term cache clearing
 */
remove_action('set_object_terms', 'wc_clear_term_product_ids', 10);