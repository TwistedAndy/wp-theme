<?php

/*
Описание: библиотека для работы с Advanced Custom Fields
Автор: Тониевич Андрей
Версия: 1.5
Дата: 18.01.2015
*/

if (tw_settings('acf', 'option_page') and function_exists('acf_add_options_page')) {
	
	acf_add_options_page(array(
		'page_title' 	=> 'Редактирование информации на сайте',
		'menu_title'	=> 'Информация',
		'menu_slug' 	=> 'theme-settings',
		'capability'	=> 'manage_options',
		'redirect'		=> false,
		'position'		=> 25,
		'icon_url'		=> 'dashicons-welcome-widgets-menus'
	));
	
}


if (tw_settings('acf', 'include_subcats')) {

	add_filter('acf/location/rule_match/post_category', 'tw_match_subcategories', 10, 3);

	function tw_match_subcategories($match, $rule, $options) {
		
		if (in_array($rule['operator'], array('==', '!=')) and strpos($rule['value'], 'category:') === 0) {
			
			$category = get_category_by_slug(urldecode(str_replace('category:', '', $rule['value'])));
			
			if ($category and $post_id = intval($options['post_id'])) {
				
				$category_id = $category->cat_ID;
				
				if ($options['ajax']) {
					$categories = $options['post_taxonomy'];
				} else {
					$categories = tw_post_categories($post_id, true, true);
				}

				if ($rule['operator'] == '==') $return = true; else $return = false;
			
				if (is_array($categories) and $categories) {
					if (in_array($category_id, $categories)) {
						return $return;
					} else {
						foreach ($categories as $category) {
							if ($parents = get_ancestors($category, 'category')) {
								if (in_array($category_id, $parents)) return $return;
							}
						}
					}
				}
				
			}
			
		} 
		
		return $match;
		
	}

}


if (tw_settings('acf', 'json_enable')) {

	add_filter('acf/settings/save_json', 'tw_json_save_point');

	function tw_json_save_point($path) {

		$path = get_stylesheet_directory() . '/library/acf';
		
		if (!is_dir($path)) mkdir($path, 0755);
		
		return $path;
		
	}


	add_filter('acf/settings/load_json', 'tw_json_load_point');

	function tw_json_load_point($paths) {
		
		unset($paths[0]);
		
		$path = get_stylesheet_directory() . '/library/acf';
		
		if (!is_dir($path)) mkdir($path, 0755);
		
		$paths[] = $path;
		
		return $paths;
		
	}

}


if (!function_exists('get_field') and !is_admin()) {
	
	function get_field($field, $post_id = false) {
		
		if (!$post_id) $post_id = intval(get_the_ID());
		
		return get_post_meta($post_id, $field, false);
		
	}


	if (tw_settings('acf', 'require_acf')) {
	
		add_action('wp_footer', 'tw_acf_fallback', 100);
		
		function tw_acf_fallback() {
			echo '<p style="font-size: 15px; font-family: sans-serif; line-height: 130%; text-align: center; padding: 10px 15px; background: #FDF4F4; border-top: 1px solid #CD9393; color: #8C3535;">Для полноценной работы шаблона необходимо включить плагин Advanced Custom Fields в настройках</p>';
		}
	
	}

}

?>