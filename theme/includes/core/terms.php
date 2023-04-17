<?php
/**
 * Terms Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/**
 * Build an array with the post terms and their parents
 *
 * @param int|WP_Post $post_id  Post ID or WP_Post object
 *
 * @param string      $taxonomy Post
 *
 * @return array
 */
function tw_terms_threads($post_id, $taxonomy) {

	if (empty($post_id)) {
		$post_id = get_the_ID();
	}

	if ($post_id instanceof WP_Post) {
		$post_id = $post_id->ID;
	}

	$threads = [];

	$categories = tw_terms_post($post_id, $taxonomy, false);

	if (is_array($categories) and $categories) {

		foreach ($categories as $category) {

			$category_thread = tw_terms_thread($category, true, false);

			if (is_array($category_thread) and $category_thread) {
				$threads[$category_thread[0]] = $category_thread;
			}

		}

	}

	return $threads;

}


/**
 * Build an array with parent and nested terms for a given term ID
 *
 * @param int|WP_Term $term             Term
 * @param bool        $include_parents  Include parent terms
 * @param bool        $include_children Include children terms
 *
 * @return array
 */
function tw_terms_thread($term, $include_parents = true, $include_children = true) {

	$result = [];

	if (is_numeric($term)) {
		$term = get_term($term);
	}

	if (is_object($term) and $term instanceof WP_Term) {

		$term_id = $term->term_id;

		$taxonomy = $term->taxonomy;

		$cache_key = $taxonomy . '_thread_' . $term_id . '_' . intval($include_parents) . intval($include_children);

		$result = wp_cache_get($cache_key, 'twee');

		if (empty($result)) {

			$result = [];

			if ($include_parents) {
				$parents = get_ancestors($term_id, $taxonomy);
				if (is_array($parents) and $parents) {
					$result = array_reverse($parents);
				}
			}

			$result[] = $term_id;

			if ($include_children) {
				$children = get_term_children($term_id, $taxonomy);
				if (is_array($children) and $children) {
					$result = array_merge($result, $children);
				}
			}

			$result = array_unique($result, SORT_NUMERIC);

			wp_cache_add($cache_key, $result, 'twee', 60);

		}

	}

	return $result;

}


/**
 * Convert a simple terms list to a hierarchical array
 *
 * @param WP_Term[] $terms  Array with terms
 * @param int       $parent Parent term ID
 *
 * @return WP_Term[]
 */
function tw_terms_tree($terms, $parent = 0) {

	$branch = [];

	foreach ($terms as $term) {

		if ($term->parent == $parent) {

			$children = tw_terms_tree($terms, $term->term_id);

			if ($children) {
				$term->children = $children;
			}

			$branch[$term->term_id] = $term;

			unset($terms[$term->term_id]);

		}

	}

	return $branch;

}


/**
 * Get the post term IDs as a comma-separated values or as an array
 *
 * @param int|WP_Post $post_id         Post ID or WP_Post object
 * @param string      $taxonomy        Post taxonomy
 * @param bool        $include_parents Include parent terms
 *
 * @return array|string
 */
function tw_terms_post($post_id, $taxonomy, $include_parents = false) {

	if (empty($post_id)) {
		$post_id = get_the_ID();
	}

	if ($post_id instanceof WP_Post) {
		$post_id = $post_id->ID;
	}

	$cache_key = 'post_' . $taxonomy . '_' . $post_id . '_' . intval($include_parents);

	$result = wp_cache_get($cache_key, 'twee');

	if (empty($result)) {

		$result = [];

		$terms = get_the_terms($post_id, $taxonomy);

		if (is_array($terms) and $terms) {

			foreach ($terms as $term) {

				$result[] = $term->term_id;

				if ($include_parents and !empty($term->parent)) {

					$result[] = $term->parent;

					$parents = get_ancestors($term->parent, $taxonomy);

					if (is_array($parents) and $parents) {
						$result = array_merge($result, array_reverse($parents));
					}

				}

			}

		}

		$result = array_unique($result, SORT_NUMERIC);

		$result = array_reverse($result);

		wp_cache_add($cache_key, $result, 'twee', 60);

	}

	return $result;

}


/**
 * Get names or links to all post terms
 *
 * @param int|WP_Post $post_id   Post ID or WP_Post object
 * @param string      $taxonomy  Term taxonomy
 * @param bool        $with_link Wrap term a link
 * @param string      $class     Link class
 *
 * @return array
 */
function tw_terms_links($post_id = false, $taxonomy = 'category', $class = 'category', $with_link = true) {

	if (empty($post_id)) {
		$post_id = get_the_ID();
	}

	if ($post_id instanceof WP_Post) {
		$post_id = $post_id->ID;
	}

	$result = [];

	$terms = get_the_terms($post_id, $taxonomy);

	if (is_array($terms) and $terms) {

		if ($class) {
			$class = ' class="' . esc_attr($class) . '"';
		}

		foreach ($terms as $term) {
			if ($with_link) {
				$result[] = '<a href="' . get_term_link($term->term_id) . '"' . $class . '>' . $term->name . '</a>';
			} else {
				if ($class) {
					$result[] = '<span' . $class . '>' . $term->name . '</span>';
				} else {
					$result[] = $term->name;
				}
			}
		}

	}

	return $result;

}