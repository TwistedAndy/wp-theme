<?php
/**
 * A set of additional filters and hooks to modify the default WordPress behaviour
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */


/**
 * Add an 'active' class for the selected item in menu and clean the menu
 */
add_filter('nav_menu_css_class', 'tw_filter_menu_class', 20);

function tw_filter_menu_class($classes) {

	$active_classes = [
		'current_page_parent',
		'current-menu-item',
		'current-menu-ancestor',
		'current-post-ancestor',
		'current-page-ancestor',
		'current-category-ancestor',
	];

	foreach ($active_classes as $class) {

		if (in_array($class, $classes)) {
			$classes[] = 'active';
			break;
		}

	}

	if (in_array('menu-item-has-children', $classes)) {
		$classes[] = 'submenu';
	}

	foreach ($classes as $key => $class) {
		if (strpos($class, 'menu-item') === 0 or strpos($class, 'current-') === 0) {
			unset($classes[$key]);
		}
	}

	return $classes;

}


/**
 * Clean the header from some meta tags and scripts
 */
if (!is_admin()) {

	add_action('after_setup_theme', 'tw_filter_headers', 20);

	function tw_filter_headers() {

		remove_action('wp_head', '_admin_bar_bump_cb');
		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'feed_links', 2);
		remove_action('wp_head', 'feed_links_extra', 3);
		remove_action('wp_head', 'wc_products_rss_feed', 10);
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'wp_shortlink_wp_head', 10);
		remove_action('wp_head', 'wp_oembed_add_host_js');
		remove_action('wp_head', 'wp_oembed_add_discovery_links');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_head', 'rest_output_link_wp_head', 10);
		remove_action('wp_print_styles', 'print_emoji_styles');

		add_filter('the_generator', '__return_false');

	}

}




/**
 * Enqueue comment reply script for threaded comments
 */
add_action('wp_enqueue_scripts', 'tw_filter_comment_styles');

function tw_filter_comment_styles() {
	if (is_singular() and comments_open() and get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}
}


/**
 * Disable pings
 */
add_filter('pings_open', '__return_false');


/**
 * Enable the jQuery Migrate plugin
 */
add_action('wp_default_scripts', function($scripts) {

	$scripts->remove('jquery');

	if (is_admin()) {
		$scripts->add('jquery', false, array('jquery-core', 'jquery-migrate'), '3.5.1');
	} else {
		$scripts->add('jquery', false, array('jquery-core'), '3.5.1');
	}

}, 20);


/**
 * Convert cyrillic symbols in urls to latin
 *
 * @param string $text
 *
 * @return string
 */
if (is_admin()) {

	add_action('sanitize_title', 'tw_filter_title', 1);

	function tw_filter_title($text) {

		$iso = [
			'Є' => 'YE', 'І' => 'I',  'Ѓ' => 'G', 'і' => 'i',  '№' => '#', 'є' => 'ye', 'ѓ' => 'g',
			'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH',
			'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
			'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'X', 'Ц' => 'C',
			'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SHH', 'Ъ' => "'", 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU',
			'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
			'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
			'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'x',
			'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e',
			'ю' => 'yu', 'я' => 'ya', '—' => '-', '«' => '', '»' => '', '…' => ''
		];

		return strtr($text, $iso);

	}

}


/**
 * Disable the Gutenberg assets
 */
add_action('wp_print_styles', function() {

	if (!is_admin()) {
		wp_dequeue_style('wp-block-library');
		wp_dequeue_style('wc-block-style');
	}

}, 100);


/**
 * Disables the block editor from managing widgets in the Gutenberg plugin
 */
add_filter('gutenberg_use_widgets_block_editor', '__return_false');


/**
 * Disables the block editor from managing widgets
 */
add_filter('use_widgets_block_editor', '__return_false');