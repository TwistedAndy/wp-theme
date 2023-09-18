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

	$cache_key = 'posts_' . $type . '_' . $key;
	$cache_group = 'twee_posts_' . $type;

	if ($value) {
		if (is_string($value)) {
			$cache_key .= '_' . $value;
		} elseif (is_array($value)) {
			$cache_key .= '_' . implode('_', $value);
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

		tw_app_set($cache_key, $posts, $cache_group);

		wp_cache_set($cache_key, $posts, $cache_group);

	}

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

	$cache_key = 'post_terms_' . $taxonomy;
	$cache_group = 'twee_post_terms_' . $taxonomy;

	$terms = tw_app_get($cache_key, $cache_group);

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

				$terms[$row['object_id']][] = (int) $row['term_id'];

			}

		}

		tw_app_set($cache_key, $terms, $cache_group);

		wp_cache_set($cache_key, $terms, $cache_group);

	}

	return $terms;

}


/**
 * Clear the post data caches
 */
add_action('save_post', function($post_id, $post) {
	if ($post instanceof WP_Post) {
		$cache_group = 'twee_posts_' . $post->post_type;
		tw_app_clear($cache_group);
		wp_cache_flush_group($cache_group);
	}
}, 10, 2);


/**
 * Clear post terms cache
 */
add_action('set_object_terms', function($object_id, $terms, $ids, $taxonomy) {
	$cache_group = 'twee_post_terms_' . $taxonomy;
	tw_app_clear($cache_group);
	wp_cache_flush_group($cache_group);
}, 10, 4);