<?php

/*
Описание: библиотека для работы с деревом страниц и категорий
Автор: Тониевич Андрей
Версия: 1.7
Дата: 05.03.2016
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

		if ($include_parents and $parents = get_ancestors($category_id, 'category')) $result = array_merge($result, array_reverse($parents));

		if ($include_children and $children = get_term_children($category_id, 'category')) $result = array_merge($result, $children);

		$result = array_unique($result, SORT_NUMERIC);

		wp_cache_add($cache_key, $result, 'twisted', 60);

		return $result;

	}

}


function tw_post_category_threads($post_id = false) {

	if ($post_id == false) $post_id = get_the_ID();

	$ancestors = array();

	if ($categories = tw_post_categories($post_id, true, false)) {
		foreach ($categories as $category) {
			if ($category_thread = tw_category_thread($category, true, false)) {
				$ancestors[$category_thread[0]] = $category_thread;
			}
		}
	}

	return $ancestors;

}


function tw_post_category_list($post_id = false, $with_link = true, $only_first = true, $class = false) {

	if ($post_id == false) $post_id = get_the_ID();

	$cache_key = 'post_categories_' . $post_id . '_object';

	$categories = wp_cache_get($cache_key, 'twisted');

	if (!$categories) {
		$categories = get_the_category($post_id);
		wp_cache_add($cache_key, $categories, 'twisted', 60);
	}

	if ($categories) {

		$result = array();

		if ($class) $class = ' class="' . $class . '"'; else $class = '';

		if ($only_first) $categories = array($categories[0]);

		foreach ($categories as $category) {
			if ($with_link) {
				$result[] = '<a href="' . get_category_link($category->cat_ID) . '"' . $class . '>' . $category->cat_name . '</a>';
			} else {
				$result[] = $category->cat_name;
			}
		}

		if ($only_first) $result = $result[0];

	} else {

		$result = '';

	}

	return $result;

}


function tw_post_categories($post_id = false, $return_array = false, $include_parent = false) {

	if ($post_id == false) $post_id = get_the_ID();

	$cache_key = 'post_categories_' . $post_id . '_' . intval($include_parent);

	$categories = wp_cache_get($cache_key, 'twisted');

	if (!$categories) {

		$categories = array();

		if ($terms = get_the_terms($post_id, 'category')) {
			foreach ($terms as $category) {
				$categories[] = $category->term_id;
				if ($include_parent and $category->parent) $categories[] = $category->parent;
			}
		}

		$categories = array_unique($categories, SORT_NUMERIC);

		wp_cache_add($cache_key, $categories, 'twisted', 60);

	}

	if ($return_array) return $categories; else return implode(',', $categories);

}


function tw_in_category($category_ids, $check_parents = true, $check_children = false) {

	$result = false;

	if (is_array($category_ids)) {
		$category_ids = array_map('intval', $category_ids);
	} else {
		$category_ids = array(intval($category_ids));
	}

	if (is_single()) {

		$categories = tw_post_categories(false, true, true);

		if (is_array($categories) and $categories) {
			foreach ($category_ids as $category_id) {
				if (in_array($category_id, $categories)) {
					$result = true;
				} elseif ($check_parents or $check_children) {
					foreach ($categories as $category) {
						if ($category_thread = tw_category_thread($category, $check_parents, $check_children)) {
							if (in_array($category_id, $category_thread)) {
								$result = true;
								break;
							}
						}
					}
				}
				if ($result) break;
			}
		}

	} elseif (is_category()) {

		$current_category_id = intval(get_query_var('cat'));

		foreach ($category_ids as $category_id) {

			if ($category_id == $current_category_id) {
				$result = true;
			} elseif ($category_thread = tw_category_thread($category_id, false, true)) {
				$result = in_array($current_category_id, $category_thread);
			}

			if ($result) break;

		}

	}

	return $result;

}


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
		if ($taxonomy == '') $taxonomy = array_shift($taxonomies);
	} elseif (is_category()) {
		$taxonomy = 'category';
	} elseif (is_tax())  {
		$taxonomy = get_query_var('taxonomy');
	}

	return $taxonomy;

}


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


function tw_current_category($return_object = false) {

	return tw_current_term($return_object, 'category');

}