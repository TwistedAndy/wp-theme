<?php
/**
 * Posts Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/**
 * Get an array with post data
 *
 * @param string $type
 * @param string $key
 * @param string $value
 *
 * @return array
 */
function tw_post_data($type, $key = 'ID', $value = 'post_title') {

	$cache_key = 'posts_' . $key;
	$cache_group = 'twee_posts_' . $type;

	$select = 'p.*';

	if ($value) {
		if (is_string($value)) {
			$cache_key .= '_' . $value;
			$select = 'p.' . $key . ', p.' . $value;
		} elseif (is_array($value)) {
			asort($value);
			$cache_key .= '_' . implode('_', $value);
			$select = 'p.' . $key . ', p.' . implode(', p.', $value);
		}
	}

	$posts = tw_app_get($cache_key, $cache_group);

	if (is_array($posts)) {
		return $posts;
	}

	$posts = wp_cache_get($cache_key, $cache_group);

	if (!is_array($posts)) {

		$posts = [];

		$db = tw_app_database();

		$rows = $db->get_results($db->prepare("SELECT {$select} FROM {$db->posts} p WHERE p.post_type = %s", $type), ARRAY_A);

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

		wp_cache_set($cache_key, $posts, $cache_group);

	}

	tw_app_set($cache_key, $posts, $cache_group);

	return $posts;

}


/**
 * Get an array with post terms
 *
 * @param string $taxonomy
 *
 * @return array
 */
function tw_post_terms($taxonomy) {

	$cache_key = 'post_terms';
	$cache_group = 'twee_post_terms_' . $taxonomy;

	$terms = tw_app_get($cache_key, $cache_group);

	if (is_array($terms)) {
		return $terms;
	}

	$terms = wp_cache_get($cache_key, $cache_group);

	if (!is_array($terms)) {

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
					$terms[$row['object_id']] = [];
				}

				$terms[$row['object_id']][] = (int) $row['term_id'];

			}

		}

		wp_cache_set($cache_key, $terms, $cache_group);

	}

	tw_app_set($cache_key, $terms, $cache_group);

	return $terms;

}


/**
 * Build the post query
 *
 * @param string $type
 * @param array  $block
 *
 * @return array
 */
function tw_post_query($type, $block) {

	if (!is_array($block)) {
		$block = [];
	}

	$taxonomies = get_object_taxonomies($type);

	$args = [
		'post_type' => $type,
		'post_status' => 'publish',
		'posts_per_page' => 6,
		'orderby' => 'date',
		'order' => 'DESC',
		'offset' => 0
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
					'field' => 'term_id',
					'terms' => $block[$taxonomy],
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

			if (empty($args['post__not_in'])) {
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
						'field' => 'term_id',
						'terms' => $terms[$object->ID],
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
				'key' => 'views_total',
				'compare' => 'EXISTS',
				'type' => 'NUMERIC'
			];

			$args['orderby'] = [
				'views' => 'DESC',
				'date' => 'DESC'
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
function tw_post_clear_cache($post_id, $post) {
	if ($post instanceof WP_Post) {
		$cache_group = 'twee_posts_' . $post->post_type;
		tw_app_clear($cache_group);
		wp_cache_flush_group($cache_group);
	}
}

add_action('save_post', 'tw_post_clear_cache', 10, 2);
add_action('delete_post', 'tw_post_clear_cache', 10, 2);


/**
 * Clear post terms cache
 */
add_action('set_object_terms', function($object_id, $terms, $ids, $taxonomy) {
	$cache_group = 'twee_post_terms_' . $taxonomy;
	tw_app_clear($cache_group);
	wp_cache_flush_group($cache_group);
}, 10, 4);