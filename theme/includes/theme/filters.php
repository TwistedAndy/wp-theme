<?php
/**
 * Additional filters and actions
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.3
 */

/**
 * Remove menu IDs
 */
add_filter('nav_menu_item_id', '__return_empty_string');


/**
 * Add an 'active' class for the selected item in menu and clean the menu
 */
function tw_filters_menu_class(array $classes, WP_Post $item, object $args, int $depth): array
{
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
		if (str_starts_with($class, 'menu-item') or str_starts_with($class, 'current-')) {
			unset($classes[$key]);
		}
	}

	return $classes;
}

add_filter('nav_menu_css_class', 'tw_filters_menu_class', 20, 4);


/**
 * Add accessibility attributes to menu wrappers
 */
function tw_filters_menu_arguments(array $args): array
{
	if (!empty($args['items_wrap']) and !str_contains($args['items_wrap'], 'role=')) {
		$args['items_wrap'] = str_replace('<ul', '<ul role="menubar"', $args['items_wrap']);
	}

	return $args;
}

add_filter('wp_nav_menu_args', 'tw_filters_menu_arguments', 10, 1);


/**
 * Add accessibility attributes to menu items
 */
function tw_filters_menu_items(string $items): string
{
	$replace = [
		'<a'  => '<a role="menuitem"',
		'<li' => '<li role="none"',
		'<ul' => '<ul role="menu"',
	];

	return str_replace(array_keys($replace), array_values($replace), $items);
}

add_filter('wp_nav_menu_items', 'tw_filters_menu_items', 10, 1);


/**
 * Disable jQuery Migrate
 */
function tw_filters_clean_jquery($scripts): void
{
	$scripts->remove('jquery');
	$scripts->add('jquery', false, ['jquery-core'], '3.7.1');
}

add_action('wp_default_scripts', 'tw_filters_clean_jquery', 20);


/**
 * Clean the header and preload block assets
 */
function tw_filters_clean_header(): void
{
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
	remove_action('wp_head', 'wp_print_auto_sizes_contain_css_fix', 1);
	remove_action('wp_head', 'wp_enqueue_img_auto_sizes_contain_css_fix', 0);

	add_filter('the_generator', '__return_false');

	/**
	 * Enqueue comment reply script for threaded comments
	 */
	if (is_singular() and comments_open() and get_option('thread_comments')) {
		wp_enqueue_script('comment-reply');
	}

}

add_action('template_redirect', 'tw_filters_clean_header');


/**
 * Disable the Gutenberg assets
 */
function tw_filters_clean_scripts(): void
{
	if (is_admin()) {
		return;
	}

	remove_action('wp_enqueue_scripts', 'wp_common_block_scripts_and_styles');
	remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');

	remove_action('wp_enqueue_scripts', 'wp_enqueue_admin_bar_bump_styles');

	remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles_custom_css');
	remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
	remove_action('wp_footer', 'wp_enqueue_global_styles', 1);

	remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
	remove_action('wp_print_styles', 'print_emoji_styles');

	/**
	 * Remove the WP Rocket Insights scripts
	 */
	if (defined('WP_ROCKET_VERSION')) {
		tw_app_remove_filter('manage_pages_columns', '\WP_Rocket\Engine\Admin\RocketInsights\PostListing\Subscriber', 'add_column_to_pages');
		tw_app_remove_filter('manage_posts_columns', '\WP_Rocket\Engine\Admin\RocketInsights\PostListing\Subscriber', 'add_column_to_posts');
		tw_app_remove_filter('manage_product_posts_columns', '\WP_Rocket\Engine\Admin\RocketInsights\PostListing\Subscriber', 'add_column_to_products', 22);
		tw_app_remove_filter('manage_pages_custom_column', '\WP_Rocket\Engine\Admin\RocketInsights\PostListing\Subscriber', 'render_rocket_insights_column');
		tw_app_remove_filter('manage_posts_custom_column', '\WP_Rocket\Engine\Admin\RocketInsights\PostListing\Subscriber', 'render_rocket_insights_column');
	}

}

add_action('init', 'tw_filters_clean_scripts', 200);


/**
 * Remove the block inline styles
 */
function tw_filters_clean_styles(): void
{
	wp_dequeue_style('wp-block-categories');
	wp_dequeue_style('wp-block-library');
	wp_dequeue_style('wp-block-heading');
	wp_dequeue_style('wp-block-archives');
	wp_dequeue_style('wp-block-group');
}

add_action('wp_footer', 'tw_filters_clean_styles', 1);


/**
 * Remove the block styles
 */
remove_action('wp_before_include_template', 'wp_start_template_enhancement_output_buffer', 1000);
remove_action('wp_template_enhancement_output_buffer_started', 'wp_hoist_late_printed_styles');


/**
 * Disables the block editor from managing widgets in the Gutenberg plugin
 */
add_filter('gutenberg_use_widgets_block_editor', '__return_false');


/**
 * Disables the block editor from managing widgets
 */
add_filter('use_widgets_block_editor', '__return_false');


/**
 * Disable pings
 */
add_filter('pings_open', '__return_false');


/**
 * Fix the pagination links
 */
add_filter('paginate_links_output', function($links) {

	return str_replace(['/page/1/"', '/page/1"', '/page/1/?', '/page/1?'], ['/"', '"', '/?', '?'], $links);

});


/**
 * Add all public terms to the link field
 */
function tw_filters_link_field(array $results, array $query): array
{
	if (empty($query['s'])) {
		return $results;
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
				'ID'        => $row->term_id,
				'title'     => $row->name,
				'permalink' => get_term_link($row, $row->taxonomy),
				'info'      => $map[$row->taxonomy]
			]);
		}
	}

	return $results;
}

add_action('wp_link_query', 'tw_filters_link_field', 10, 2);


/**
 * Cache the metadata section on the post edit screen
 */
function tw_filters_form_keys(array $keys, WP_Post $post): array
{
	$raw = get_metadata_raw('post', $post->ID);

	if (is_array($raw)) {
		$keys = array_keys($raw);
	}

	return $keys;
}

add_filter('postmeta_form_keys', 'tw_filters_form_keys', 20, 2);