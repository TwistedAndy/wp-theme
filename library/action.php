<?php

/*
Описание: дополнительные функции
Автор: Тониевич Андрей
Версия: 1.5
Дата: 18.01.2015
*/

add_filter('nav_menu_css_class' , 'tw_nav_class' , 10 , 2);

function tw_nav_class($classes, $item){

	$active_classes = array(
		'current-menu-item',
		'current-menu-ancestor',
		'current-post-ancestor',
		'current-page-ancestor',
		'current-category-ancestor',
	);
	
	foreach ($active_classes as $class) {
		
		if (in_array($class, $classes)) {
			$classes[] = 'active';
			break;
		}
		
	}
	
	return $classes;

}


if (tw_settings('init', 'get_posts_filter')) {

	add_action('pre_get_posts', 'tw_pre_get', 1);

	function tw_pre_get($query) {
		global $wp_query;
		if ($query->is_main_query() && $query->is_category(4)) {
			$query->query_vars['posts_per_page'] = 4;
			$query->set('orderby', 'name');
			return;
		}
			
	}

}

?>
