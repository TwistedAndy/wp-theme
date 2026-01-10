<?php
/**
 * Posts Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
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
	$cache_group = 'twee_posts_' . $type;

	$select = 'p.*';

	if ($fields) {

		if (is_string($fields) and strpos($fields, ',') > 0) {
			$fields = explode(',', $fields);
		}

		if (is_string($fields)) {
			$cache_key .= '_' . $fields;
			$select = 'p.' . $key . ', p.' . $fields;
		} elseif (is_array($fields)) {
			$fields = array_map('trim', $fields);
			asort($fields);
			$cache_key .= '_' . implode('_', $fields);
			$select = 'p.' . $key . ', p.' . implode(', p.', $fields);
		}

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

	$select = esc_sql($select);

	if ($status) {
		if (strpos($status, ',') > 0) {
			$parts = array_map('trim', explode(',', esc_sql($status)));
			$where = " AND p.post_status IN ('" . implode("','", $parts) . "')";
		} else {
			$where = " AND p.post_status = '" . esc_sql($status) . "'";
		}
	} else {
		$where = '';
	}

	$rows = $db->get_results($db->prepare("SELECT {$select} FROM {$db->posts} p WHERE p.post_type = %s" . $where . " ORDER BY %s", $type, $order), ARRAY_A);

	if (is_array($fields)) {
		foreach ($rows as $row) {
			$array = [];
			foreach ($fields as $field) {
				$array[$field] = $row[$field] ?? '';
			}
			$data[$row[$key]] = $array;

		}
	} elseif ($fields and is_string($fields)) {
		foreach ($rows as $row) {
			$data[$row[$key]] = $row[$fields];
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