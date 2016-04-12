<?php

/*
Описание: дополнительные фильтры и хуки
Автор: Тониевич Андрей
Версия: 1.7
Дата: 14.03.2016
*/

if (tw_settings('init', 'action_menu_active')) {

	add_filter('nav_menu_css_class' , 'tw_nav_class', 10, 2);

	function tw_nav_class($classes) {

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

}


if (!is_admin() and tw_settings('init', 'action_clean_header')) {

	add_action('after_setup_theme', 'tw_clean_header', 10);

	function tw_clean_header() {

		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'rel_canonical');
		remove_action('wp_head', 'feed_links', 10);
		remove_action('wp_head', 'feed_links_extra', 10);
		remove_action('wp_head', 'wc_products_rss_feed', 10);
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'index_rel_link');
		remove_action('wp_head', 'wp_shortlink_wp_head');
		remove_action('wp_head', 'wp_oembed_add_host_js');
		remove_action('wp_head', 'wp_oembed_add_discovery_links');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_head', 'rest_output_link_wp_head', 10);
		remove_action('wp_print_styles', 'print_emoji_styles');

		add_filter('the_generator', '__return_false');

	}


	add_filter('wp_default_scripts', 'tw_remove_jquery_migrate');

	function tw_remove_jquery_migrate($scripts) {

		$scripts->remove('jquery');
		$scripts->add('jquery', false, array('jquery-core'));

		return $scripts;

	}

}


if (tw_settings('init', 'action_get_posts')) {

	add_action('pre_get_posts', 'tw_pre_get', 1);

	function tw_pre_get($query) {

		if ($query->is_main_query() && $query->is_category(4)) {

			$query->query_vars['posts_per_page'] = 4;

			$query->set('orderby', 'name');

			return;

		}

	}

}