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

	if ($type) {
		$where = "WHERE p.post_type = '" . esc_sql($type) . "'";
	} else {
		$where = '';
	}

	if ($status) {
		if (empty($where)) {
			$where = 'WHERE';
		}

		if (strpos($status, ',') > 0) {
			$parts = array_map('trim', explode(',', $db->_escape($status)));
			$where .= " AND p.post_status IN ('" . implode("','", $parts) . "')";
		} else {
			$where .= " AND p.post_status = '" . $db->_escape($status) . "'";
		}
	}

	$rows = $db->get_results("SELECT {$select} FROM {$db->posts} p " . $where . " ORDER BY " . $db->_escape($order), ARRAY_A);

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
	$cache_key = 'post_term_thread';
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

	$terms_map = tw_post_terms($taxonomy);

	if (empty($terms_map[$post_id]) or !is_array($terms_map[$post_id])) {
		wp_cache_set($cache_key, $thread, $cache_group);

		return $thread;
	}

	foreach ($terms_map[$post_id] as $term) {

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
 * Sync the post terms
 *
 * @param int    $post_id
 * @param int[]  $term_ids
 * @param string $taxonomy
 * @param bool   $append
 *
 * @return bool
 */
function tw_post_set_terms(int $post_id, array $term_ids, string $taxonomy, bool $append = false): bool
{
	$old_term_ids = tw_post_get_terms($post_id, $taxonomy);

	if ($append and $old_term_ids) {
		$new_term_ids = array_values(array_unique(array_merge($term_ids, $old_term_ids)));
	} else {
		$new_term_ids = $term_ids;
	}

	$count_old = count($old_term_ids);
	$count_new = count($new_term_ids);

	if ($count_old !== $count_new) {
		$update_terms = true;
	} elseif ($count_old === 0) {
		$update_terms = false;
	} else {
		sort($new_term_ids);
		sort($old_term_ids);

		$update_terms = ($new_term_ids !== $old_term_ids);
	}

	if ($update_terms) {
		wp_set_object_terms($post_id, $term_ids, $taxonomy, false);

		return true;
	}

	return false;
}


/**
 * Build the post query
 *
 * @param string $type
 * @param array  $block
 *
 * @return array
 */
function tw_post_query(string $type, array $block = []): array
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
	tw_app_clear('twee_posts_' . $post->post_type);
}

add_action('save_post', 'tw_post_clear_cache', 10, 2);
add_action('delete_post', 'tw_post_clear_cache', 10, 2);


/**
 * Clear post terms cache
 */
function tw_post_clear_terms(int $object_id, array $terms, array $ids, string $taxonomy): void
{
	tw_app_clear('twee_post_terms_' . $taxonomy);
}

add_action('set_object_terms', 'tw_post_clear_terms', 10, 4);