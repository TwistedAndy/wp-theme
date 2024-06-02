<?php
/**
 * Additional filters and actions
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/**
 * Remove menu IDs
 */
add_filter('nav_menu_item_id', '__return_empty_string');


/**
 * Add an 'active' class for the selected item in menu and clean the menu
 */
add_filter('nav_menu_css_class', function($classes) {

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

}, 20);


/**
 * Add accessibility attributes to menu wrappers
 */
add_filter('wp_nav_menu_args', function($args) {

	if (!empty($args['items_wrap']) and strpos($args['items_wrap'], 'role=') === false) {
		$args['items_wrap'] = str_replace('<ul', '<ul role="menubar"', $args['items_wrap']);
	}

	return $args;

}, 10, 1);


/**
 * Add accessibility attributes to menu items
 */
add_filter('wp_nav_menu_items', function($items, $args) {

	$replace = [
		'<a' => '<a role="menuitem"',
		'<li' => '<li role="none"',
		'<ul' => '<ul role="menu"',
	];

	return str_replace(array_keys($replace), array_values($replace), $items);

}, 10, 2);


/**
 * Clean the header and preload block assets
 */
add_action('wp_head', function() {

	if (is_admin()) {
		return;
	}

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

	add_filter('the_generator', '__return_false');

}, 3);


/**
 * Enqueue comment reply script for threaded comments
 */
add_action('wp_enqueue_scripts', function() {
	if (is_singular() and comments_open() and get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}
});


/**
 * Disable pings
 */
add_filter('pings_open', '__return_false');


/**
 * Disable jQuery Migrate
 */
add_action('wp_default_scripts', function($scripts) {
	$scripts->remove('jquery');
	$scripts->add('jquery', false, ['jquery-core'], '3.7.1');
}, 20);


/**
 * Disable the Gutenberg assets
 */
add_action('init', function() {

	if (!is_admin()) {

		remove_action('wp_enqueue_scripts', 'wp_common_block_scripts_and_styles');
		remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');

		remove_action('wp_enqueue_scripts', 'wp_enqueue_admin_bar_bump_styles');

		remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles_custom_css');
		remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
		remove_action('wp_footer', 'wp_enqueue_global_styles', 1);

		remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
		remove_action('wp_print_styles', 'print_emoji_styles');

	}

}, 200);


/**
 * Disables the block editor from managing widgets in the Gutenberg plugin
 */
add_filter('gutenberg_use_widgets_block_editor', '__return_false');


/**
 * Disables the block editor from managing widgets
 */
add_filter('use_widgets_block_editor', '__return_false');


/**
 * Add all public terms to the links field
 */
add_action('wp_link_query', function($results, $query) {

	if (is_array($query) and !empty($query['s'])) {

		if (!is_array($results)) {
			$results = [];
		}

		$taxonomies = get_taxonomies(['public' => true], 'objects');

		if (empty($taxonomies)) {
			return $results;
		}

		$map = [];

		foreach ($taxonomies as $taxonomy) {
			$map[$taxonomy->name] = $taxonomy->label;
		}

		if (empty($map)) {
			return $results;
		}

		$db = tw_app_database();

		$rows = $db->get_results("SELECT t.*, tt.taxonomy FROM {$db->terms} t LEFT JOIN {$db->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.name LIKE '%" . $db->esc_like($query['s']) . "%' AND tt.taxonomy IN ('" . implode("','", array_keys($map)) . "')", OBJECT);

		if (empty($rows)) {
			return $results;
		}

		foreach ($rows as $row) {

			if (!empty($row->taxonomy) and !empty($map[$row->taxonomy])) {

				array_unshift($results, [
					'ID' => $row->term_id,
					'title' => $row->name,
					'permalink' => get_term_link($row, $row->taxonomy),
					'info' => $map[$row->taxonomy]
				]);

			}

		}

	}

	return $results;

}, 10, 2);