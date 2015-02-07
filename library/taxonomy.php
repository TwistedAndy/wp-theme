<?php

/*
Описание: библиотека для работы с деревом страниц и категорий
Автор: Тониевич Андрей
Версия: 1.5
Дата: 18.01.2015
*/

function tw_category_link($post_id = false, $return_link = true, $only_first = true, $class = false) {

	if ($post_id == false) $post_id = get_the_ID();
	
	if ($categories = get_the_category($post_id)) {
		
		$result = array();
		
		if ($class) $class = ' class="' . $class . '"'; else $class = '';
		
		if ($only_first) $categories = array($categories[0]);
		
		foreach ($categories as $category) {
			if ($return_link) {
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


function tw_current_category($return_object = false) {
	
	$category_id = 0;
	
	if (is_single()) {
		if ($cs = get_the_category(get_the_ID())) $category_id = $cs[0]->cat_ID;
	} else if (is_category()) {
		$category_id = get_query_var('cat');
	} else {
		return 0;
	}
	
	if ($return_object and $category_id) {
		return get_category($category_id);
	} else {
		return intval($category_id);
	}

}


function tw_post_categories($post_id = false, $return_array = false, $include_parent = false) {

	if ($post_id == false) $post_id = get_the_ID();

	$cache_key = 'post_categories_' . $post_id . '_' . intval($include_parent);
	
	$categories = wp_cache_get($cache_key, 'twisted');
	
	if ($categories === false) {
		
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


function tw_in_category($category_id, $check_parents = true, $check_children = false) {

	$result = false;
	
	$category_id = intval($category_id);
	
	if (is_single()) {
		
		$categories = tw_post_categories(false, true, true);
		
		if (is_array($categories) and $categories) {
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
		}

	} elseif (is_category()) {
		
		$current_category_id = intval(get_query_var('cat'));
		
		if ($category_id == $current_category_id) {
			$result = true;
		} elseif ($category_thread = tw_category_thread($category_id, false, true)) {
			$result = in_array($current_category_id, $category_thread);
		}
		
	}
	
	return $result;
	
}


function tw_in_page($page_id, $check_all_children = false) {

	$result = false;
	
	if (is_page()) {
		
		$current_page_id = get_the_ID();
		
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

?>