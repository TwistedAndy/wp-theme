<?php
/**
 * Taxonomy library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Build an array with parent and nested terms for a given term ID
 *
 * @param bool|int $term_id          Term ID
 * @param bool     $include_parents  Include the parent terms
 * @param bool     $include_children Include the nested terms
 *
 * @return array
 */

function tw_term_thread($term_id = false, $include_parents = true, $include_children = true) {

	$result = array();

	if ($term_id == false) {
		$term_id = tw_current_term(false);
	}

	$term = get_term($term_id);

	if (is_object($term) and $term instanceof WP_Term) {

		$term_id = $term->term_id;

		$taxonomy = $term->taxonomy;

		$cache_key = $taxonomy . '_thread_' . $term_id . '_' . intval($include_parents) . intval($include_children);

		$result = wp_cache_get($cache_key, 'twisted');

		if (empty($result)) {

			$result = array();

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

			wp_cache_add($cache_key, $result, 'twisted', 60);

		}

	}

	return $result;

}


/**
 * Convert a simple terms list with a hierarchical array
 *
 * @param WP_Term[] $terms  Array with terms
 * @param int       $parent Parent term ID
 *
 * @return WP_Term[]
 */

function tw_term_tree($terms, $parent = 0) {

	$branch = array();

	foreach ($terms as $term) {

		if ($term->parent == $parent) {

			$children = tw_term_tree($terms, $term->term_id);

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
 * Build an array with the post terms and their parents
 *
 * @param bool|int    $post_id  Post ID or false for the current post
 *
 * @param bool|string $taxonomy Post
 *
 * @return array
 */

function tw_post_term_threads($post_id = false, $taxonomy = false) {

	if (empty($post_id)) {
		$post_id = get_the_ID();
	}

	if (empty($taxonomy)) {
		$taxonomy = tw_post_taxonomy($post_id);
	}

	$threads = array();

	$categories = tw_post_terms($post_id, $taxonomy, true, false);

	if (is_array($categories) and $categories) {

		foreach ($categories as $category) {

			$category_thread = tw_term_thread($category, true, false);

			if (is_array($category_thread) and $category_thread) {
				$threads[$category_thread[0]] = $category_thread;
			}

		}

	}

	return $threads;

}


/**
 * Get the post term IDs as a comma-separated values or as an array
 *
 * @param bool|int    $post_id         Post ID or false for the current post
 * @param bool|string $taxonomy        Post taxonomy
 * @param bool        $return_as_array Return terms as an array
 * @param bool        $include_parents Include parent terms
 *
 * @return array|string
 */

function tw_post_terms($post_id = false, $taxonomy = false, $return_as_array = false, $include_parents = false) {

	if (empty($taxonomy)) {
		$taxonomy = tw_post_taxonomy($post_id);
	}

	if (empty($post_id)) {
		$post_id = get_the_ID();
	}

	$cache_key = 'post_' . $taxonomy . '_' . $post_id . '_' . intval($include_parents);

	$categories = wp_cache_get($cache_key, 'twisted');

	if (empty($categories)) {

		$categories = array();

		$terms = get_the_terms($post_id, $taxonomy);

		if (is_array($terms) and $terms) {

			foreach ($terms as $term) {

				$categories[] = $term->term_id;

				if ($include_parents and !empty($term->parent)) {

					$categories[] = $term->parent;

					$parents = get_ancestors($term->parent, $taxonomy);

					if (is_array($parents) and $parents) {
						$categories = array_merge($categories, array_reverse($parents));
					}

				}

			}

		}

		$categories = array_unique($categories, SORT_NUMERIC);

		$categories = array_reverse($categories);

		wp_cache_add($cache_key, $categories, 'twisted', 60);

	}

	if (!$return_as_array) {
		$categories = implode(',', $categories);
	}

	return $categories;

}


/**
 * Get names or links to all post terms
 *
 * @param bool|int $post_id   Post ID or false for the current post
 * @param bool     $with_link Wrap each term with the link
 * @param string   $class     Link class
 * @param string   $taxonomy  Term taxonomy
 *
 * @return array
 */

function tw_post_term_list($post_id = false, $taxonomy = 'category', $with_link = true, $class = '') {

	if (empty($post_id)) {
		$post_id = get_the_ID();
	}

	if (empty($taxonomy) or !is_string($taxonomy)) {
		$taxonomy = tw_post_taxonomy($post_id);
	}

	$result = array();

	$terms = get_the_terms($post_id, $taxonomy);

	if (is_array($terms) and $terms) {

		if ($class) {
			$class = ' class="' . $class . '"';
		}

		foreach ($terms as $term) {
			if ($with_link) {
				$result[] = '<a href="' . get_term_link($term->term_id) . '"' . $class . '>' . $term->name . '</a>';
			} else {
				$result[] = $term->name;
			}
		}

	}

	return $result;

}


/**
 * Get the link to the first post category
 *
 * @param bool|int $post_id  Post ID or false for the current post
 * @param string   $taxonomy Term taxonomy
 * @param string   $class    Link class
 *
 * @return string
 */

function tw_post_term_link($post_id = false, $taxonomy = 'category', $class = '') {

	$result = '';

	$categories = tw_post_term_list($post_id, $taxonomy, true, $class);

	if ($categories and !empty($categories[0])) {

		$result = $categories[0];

	}

	return $result;

}


/**
 * Check if the current post or term belongs to specified terms
 *
 * @param array|int      $terms          Single category ID or an array with IDs to check
 * @param array|int|bool $current_terms  Current category ID. Set false to use current category ID
 * @param string         $taxonomy       Term taxonomy
 * @param bool           $check_parents  Check the parent categories for the current ones
 * @param bool           $check_children Check the children categories for the current ones
 *
 * @return bool
 */

function tw_in_terms($terms, $taxonomy = 'category', $current_terms = false, $check_parents = true, $check_children = false) {

	$result = false;

	if (is_array($terms)) {
		$terms = array_map('intval', $terms);
	} else {
		$terms = array(intval($terms));
	}

	if ($current_terms or is_single()) {

		if ($current_terms) {

			if (is_array($current_terms)) {
				$current_terms = array_map('intval', $current_terms);
			} else {
				$current_terms = array(intval($current_terms));
			}

		} else {

			$current_terms = tw_post_terms(false, $taxonomy, true, true);

		}

		if (is_array($current_terms) and $current_terms) {

			foreach ($terms as $term) {

				if (in_array($term, $current_terms)) {

					$result = true;

				} elseif ($check_parents or $check_children) {

					foreach ($current_terms as $current_term) {

						$term_thread = tw_term_thread($current_term, $check_parents, $check_children);

						if (is_array($term_thread) and $term_thread) {

							if (in_array($term, $term_thread)) {
								$result = true;
								break;
							}

						}

					}

				}

				if ($result) {
					break;
				}

			}

		}

	} elseif (is_category() or is_tax()) {

		$current_term = get_queried_object_id();

		foreach ($terms as $term) {

			if ($term == $current_term) {

				$result = true;

			} else {

				$term_thread = tw_term_thread($term, false, true);

				if (is_array($term_thread) and $term_thread) {
					$result = in_array($current_term, $term_thread);
				}

			}

			if ($result) {
				break;
			}

		}

	}

	return $result;

}


/**
 * Check if the current page is nested in another
 *
 * @param      $page_id
 * @param bool $check_all_children
 *
 * @return bool
 */

function tw_in_page($page_id, $check_all_children = false) {

	$result = false;

	if (is_page()) {

		$current_page_id = get_the_ID();
		$children = false;

		if ($current_page_id == $page_id) {
			$result = true;
		} elseif ($check_all_children) {
			$children = get_page_children($page_id, get_pages());
		} else {
			$children = get_pages(array('parent' => $page_id));
		}

		if ($children) {
			foreach ($children as $child) {
				if ($child->ID == $current_page_id) {
					$result = true;
					break;
				}
			}
		}

	}

	return $result;

}


/**
 * Get the post taxonomy
 *
 * @param bool|int|WP_Post $post Post ID or WP_Post object. Default is global $post.
 *
 * @return string
 */

function tw_post_taxonomy($post = false) {

	$post = get_post($post);

	$taxonomy = '';

	if (is_object($post) and $post instanceof WP_Post) {

		if ($post->post_type == 'post') {

			$taxonomy = 'category';

		} else {

			$taxonomies = get_object_taxonomies($post);

			if ($taxonomies) {

				$preferred_taxonomies = array('category', 'product_cat', 'post_tag', 'product_tag');

				foreach ($preferred_taxonomies as $preferred_taxonomy) {

					if (in_array($preferred_taxonomy, $taxonomies)) {
						$taxonomy = $preferred_taxonomy;
						break;
					}

				}

				if (empty($taxonomy)) {
					$taxonomy = array_shift($taxonomies);
				}

			}

		}

	}

	return $taxonomy;

}


/**
 * Get the current taxonomy name
 *
 * @return string
 */

function tw_current_taxonomy() {

	$taxonomy = '';

	if (is_single()) {

		$taxonomy = tw_post_taxonomy();

	} elseif (is_category()) {

		$taxonomy = 'category';

	} elseif (is_tax()) {

		$taxonomy = get_query_var('taxonomy');

	}

	return $taxonomy;

}


/**
 * Get the current term
 *
 * @param bool        $return_object Return term as an object
 * @param bool|string $taxonomy      Taxonomy name
 *
 * @return int|WP_Term
 */

function tw_current_term($return_object = false, $taxonomy = false) {

	$term_id = 0;

	if (is_single()) {

		if (empty($taxonomy)) {
			$taxonomy = tw_current_taxonomy();
		}

		$post_terms = get_the_terms(get_the_ID(), $taxonomy);

		if (is_array($post_terms) and !empty($post_terms[0]->term_id)) {

			$term_id = $post_terms[0]->term_id;

		}

	} elseif (is_category()) {

		$term_id = get_query_var('cat');

	} elseif (is_tax()) {

		$term_object = get_queried_object();

		if (is_object($term_object) and !empty($term_object->term_id)) {

			$term_id = $term_object->term_id;

		}

	}

	if ($term_id and $return_object) {
		$term_id = get_term($term_id);
	}

	return $term_id;

}