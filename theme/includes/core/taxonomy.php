<?php
/**
 * Taxonomy library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Build an array with parent and nested categories for a given category ID
 *
 * @param bool|int $category_id      Category ID
 * @param bool     $include_parents  Include the parent categories
 * @param bool     $include_children Include the nested categories
 *
 * @return array
 */

function tw_category_thread($category_id = false, $include_parents = true, $include_children = true) {

	$result = array();

	if ($category_id == false) {
		if (is_category()) {
			$category_id = intval(get_query_var('cat'));
		} else {
			return $result;
		}
	}

	$cache_key = 'category_thread_' . $category_id . '_' . intval($include_parents) . intval($include_children);

	$cache = wp_cache_get($cache_key, 'twisted');

	if ($cache !== false) {

		return $cache;

	} else {

		$result[] = $category_id;

		if ($include_parents) {
			$parents = get_ancestors($category_id, 'category');
			if (is_array($parents) and $parents) {
				$result = array_merge($result, array_reverse($parents));
			}
		}

		if ($include_children) {
			$children = get_term_children($category_id, 'category');
			if (is_array($children) and $children) {
				$result = array_merge($result, $children);
			}
		}

		$result = array_unique($result, SORT_NUMERIC);

		wp_cache_add($cache_key, $result, 'twisted', 60);

		return $result;

	}

}


/**
 * Build an array of categories with their parents for a given post
 *
 * @param bool|int $post_id Post ID or false for the current post
 *
 * @return array
 */

function tw_post_category_threads($post_id = false) {

	if ($post_id == false) {
		$post_id = get_the_ID();
	}

	$ancestors = array();

	$categories = tw_post_categories($post_id, true, false);

	if (is_array($categories) and $categories) {
		foreach ($categories as $category) {
			$category_thread = tw_category_thread($category, true, false);
			if (is_array($category_thread) and $category_thread) {
				$ancestors[$category_thread[0]] = $category_thread;
			}
		}
	}

	return $ancestors;

}


/**
 * Get names or links to all post categories
 *
 * @param bool|int $post_id    Post ID or false for the current post
 * @param bool     $with_link  Wrap each category with the link
 * @param string   $class      Link class
 *
 * @return array
 */

function tw_post_category_list($post_id = false, $with_link = true, $class = '') {

	$result = array();

	if ($post_id == false) {
		$post_id = get_the_ID();
	}

	$cache_key = 'post_categories_' . $post_id . '_object';

	$categories = wp_cache_get($cache_key, 'twisted');

	if (!$categories) {
		$categories = get_the_category($post_id);
		wp_cache_add($cache_key, $categories, 'twisted', 60);
	}

	if (is_array($categories) and $categories) {

		if ($class) {
			$class = ' class="' . $class . '"';
		}

		foreach ($categories as $category) {
			if ($with_link) {
				$result[] = '<a href="' . get_category_link($category->cat_ID) . '"' . $class . '>' . $category->cat_name . '</a>';
			} else {
				$result[] = $category->cat_name;
			}
		}

	}

	return $result;

}


/**
 * Get the link to the first post category
 *
 * @param bool|int $post_id Post ID or false for the current post
 * @param string   $class   Link class
 *
 * @return string
 */

function tw_post_category_link($post_id = false, $class = '') {

	$result = '';

	$categories = tw_post_category_list($post_id, true, $class);

	if ($categories and !empty($categories[0])) {

		$result = $categories[0];

	}

	return $result;

}


/**
 * Get all post categories as a comma-separated values or an array
 *
 * @param bool|int $post_id         Post ID or false for the current post
 * @param bool     $return_as_array Return categories as an array
 * @param bool     $include_parent  Include parent categories
 *
 * @return array|string
 */

function tw_post_categories($post_id = false, $return_as_array = false, $include_parent = false) {

	if ($post_id == false) {
		$post_id = get_the_ID();
	}

	$cache_key = 'post_categories_' . $post_id . '_' . intval($include_parent);

	$categories = wp_cache_get($cache_key, 'twisted');

	if (!$categories) {

		$categories = array();

		$terms = get_the_terms($post_id, 'category');

		if (is_array($terms) and $terms) {
			foreach ($terms as $category) {
				$categories[] = $category->term_id;
				if ($include_parent and $category->parent) {
					$categories[] = $category->parent;
				}
			}
		}

		$categories = array_unique($categories, SORT_NUMERIC);

		wp_cache_add($cache_key, $categories, 'twisted', 60);

	}

	if ($return_as_array) {
		return $categories;
	} else {
		return implode(',', $categories);
	}

}


/**
 * Check if the current post or category belongs to a given categories
 *
 * @param array|int      $category_ids         Single category ID or an array with IDs to check
 * @param array|int|bool $current_category_ids Current category ID. Set false to use current category ID
 * @param bool           $check_parents        Check the parent categories for the current ones
 * @param bool           $check_children       Check the children categories for the current ones
 *
 * @return bool
 */

function tw_in_category($category_ids, $current_category_ids = false, $check_parents = true, $check_children = false) {

	$result = false;

	if (is_array($category_ids)) {
		$category_ids = array_map('intval', $category_ids);
	} else {
		$category_ids = array(intval($category_ids));
	}

	if ($current_category_ids or is_single()) {

		if ($current_category_ids) {
			if (is_array($current_category_ids)) {
				$categories = array_map('intval', $current_category_ids);
			} else {
				$categories = array(intval($current_category_ids));
			}
		} else {
			$categories = tw_post_categories(false, true, true);
		}

		if (is_array($categories) and $categories) {

			foreach ($category_ids as $category_id) {

				if (in_array($category_id, $categories)) {
					$result = true;
				} elseif ($check_parents or $check_children) {
					foreach ($categories as $category) {
						$category_thread = tw_category_thread($category, $check_parents, $check_children);
						if (is_array($category_thread) and $category_thread) {
							if (in_array($category_id, $category_thread)) {
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

	} elseif (is_category()) {

		$current_category_id = intval(get_query_var('cat'));

		foreach ($category_ids as $category_id) {

			if ($category_id == $current_category_id) {
				$result = true;
			} else {
				$category_thread = tw_category_thread($category_id, false, true);
				if (is_array($category_thread) and $category_thread) {
					$result = in_array($current_category_id, $category_thread);
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
 * Get the current taxonomy name
 *
 * @return string
 */

function tw_current_taxonomy() {

	$taxonomy = '';

	if (is_single() and $taxonomies = get_post_taxonomies(get_the_ID())) {
		$preferred_taxonomies = array('category', 'product_cat', 'post_tag', 'product_tag');
		foreach ($preferred_taxonomies as $preferred_taxonomy) {
			if (in_array($preferred_taxonomy, $taxonomies)) {
				$taxonomy = $preferred_taxonomy;
				break;
			}
		}
		if ($taxonomy == '') {
			$taxonomy = array_shift($taxonomies);
		}
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

	if ($taxonomy or $taxonomy = tw_current_taxonomy()) {

		if (is_single() and $cs = get_the_terms(get_the_ID(), $taxonomy)) {
			$term_id = $cs[0]->term_id;
		} elseif (is_category()) {
			$term_id = get_query_var('cat');
		} elseif (is_tax() and $term_object = get_queried_object()) {
			$term_id = $term_object->term_id;
		} else {
			return $term_id;
		}

		if ($term_id and $return_object) {
			return get_term($term_id, $taxonomy);
		} else {
			return intval($term_id);
		}

	} else {

		return $term_id;

	}

}


/**
 * Get the current category
 *
 * @param bool $return_object
 *
 * @return int|WP_Term
 */

function tw_current_category($return_object = false) {

	return tw_current_term($return_object, 'category');

}