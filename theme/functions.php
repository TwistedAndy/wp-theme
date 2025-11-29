<?php

define('TW_THEME_GAP', 20);
define('TW_THEME_WIDTH', 1420);

define('TW_ROOT', __DIR__ . '/');
define('TW_INC', TW_ROOT . 'includes/');
define('TW_URL', get_stylesheet_directory_uri() . '/');
define('TW_HOME', untrailingslashit(get_site_url()));
define('TW_CACHE', wp_using_ext_object_cache());

$url = parse_url(TW_HOME);
define('TW_FOLDER', (is_array($url) and !empty($url['path'])) ? $url['path'] : '');

include TW_INC . 'core/app.php';

tw_app_include(TW_INC . 'core');
tw_app_include(TW_INC . 'theme');
tw_app_include(TW_INC . 'widgets');


tw_asset_register([
	'base' => [
		'style' => 'base.css',
		'inline' => '',
		'footer' => false,
		'display' => true,
		'directory' => 'build'
	],
	'other' => [
		'style' => 'other.css',
		'footer' => true,
		'display' => true,
		'directory' => 'build'
	],
	'scripts' => [
		'footer' => true,
		'display' => true,
		'deps' => ['jquery', 'app'],
		'script' => 'scripts.js',
		'object' => 'tw_template',
		'directory' => 'build',
		'localize' => function() {
			return [
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('ajax-nonce')
			];
		}
	]
]);


tw_image_sizes([
	'thumbnail' => [
		'label' => 'Thumbnail',
		'width' => 300,
		'height' => 240,
		'thumb' => true,
		'aspect' => false,
		'crop' => ['center', 'center']
	],
	'medium' => [
		'width' => 600,
		'height' => 480
	],
	'large' => [
		'width' => 0,
		'height' => 0,
		'crop' => false
	]
]);


tw_app_features([
	'title-tag',
	'post-thumbnails',
	'html5' => [
		'comment-list',
		'comment-form',
		'search-form',
		'gallery',
		'caption',
		'style',
		'script'
	]
]);


tw_app_menus([
	'main' => 'Main Menu'
]);


tw_app_type('block_set', [
	'labels' => [
		'name' => 'Block Sets',
		'singular_name' => 'Block Set',
		'new_item' => 'New Set',
		'add_new' => 'Add New',
		'add_new_item' => 'Add New Set',
		'edit_item' => 'Edit Set',
		'view_item' => 'View Set',
		'all_items' => 'All Sets',
		'search_items' => 'Search Sets',
		'not_found' => 'Set not found',
		'not_found_in_trash' => 'Set not found in trash',
	],
	'description' => 'Block Sets',
	'public' => false,
	'publicly_queryable' => false,
	'show_ui' => true,
	'show_in_menu' => true,
	'show_in_nav_menus' => true,
	'show_in_admin_bar' => true,
	'menu_position' => 20,
	'menu_icon' => 'dashicons-schedule',
	'hierarchical' => false,
	'supports' => ['title'],
	'has_archive' => false,
	'rewrite' => false,
	'query_var' => false,
]);


tw_app_type('review', [
	'labels' => [
		'name' => 'Reviews',
		'singular_name' => 'Review',
		'new_item' => 'New Review',
		'add_new' => 'Add Review',
		'add_new_item' => 'Add New',
		'edit_item' => 'Edit Review',
		'view_item' => 'VIew Review',
		'all_items' => 'All Reviews',
		'search_items' => 'Search Reviews',
		'not_found' => 'Review not found',
		'not_found_in_trash' => 'Review not found in trash',
	],
	'description' => 'Customer Reviews',
	'public' => true,
	'publicly_queryable' => true,
	'show_ui' => true,
	'show_in_menu' => true,
	'show_in_nav_menus' => true,
	'show_in_admin_bar' => true,
	'menu_position' => 10,
	'menu_icon' => 'dashicons-format-chat', // https://developer.wordpress.org/resource/dashicons/
	'hierarchical' => false,
	'supports' => ['title', 'editor', 'excerpt', 'author', 'thumbnail', 'custom-fields', 'comments'],
	'has_archive' => 'reviews',
	'rewrite' => ['slug' => 'review', 'with_front' => true, 'hierarchical' => false],
	'query_var' => true,
	'taxonomies' => ['review_category']
]);


tw_app_taxonomy('review_category', ['review'], [
	'label' => 'Categories',
	'labels' => [
		'name' => 'Categories',
		'singular_name' => 'Category',
		'search_items' => 'Search Category',
		'all_items' => 'All Categories',
		'parent_item' => 'Parent Category',
		'parent_item_colon' => 'Parent:',
		'edit_item' => 'Edit Category',
		'update_item' => 'Update Category',
		'add_new_item' => 'Add New',
		'new_item_name' => 'Name',
		'menu_name' => 'Categories',
	],
	'query_var' => true,
	'public' => true,
	'show_in_nav_menus' => true,
	'show_ui' => true,
	'show_tagcloud' => false,
	'hierarchical' => true,
	'update_count_callback' => '',
	'rewrite' => ['slug' => 'reviews', 'with_front' => true, 'hierarchical' => true],
	'show_admin_column' => true,
	'show_in_quick_edit' => null,
]);


tw_app_sidebar([
	'name' => 'Footer',
	'id' => 'footer',
	'description' => '',
	'before_widget' => '<div class="item widget %2$s">',
	'after_widget' => '</div>',
	'before_title' => '<div class="title">',
	'after_title' => '</div>'
]);


tw_app_widget('Posts');