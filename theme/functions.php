<?php

use Twee\App;

define('TW_ROOT', __DIR__ . '/');
define('TW_INC', TW_ROOT . 'includes/');
define('TW_URL', get_stylesheet_directory_uri() . '/');

include TW_INC . 'core/app.php';

$app = App::getApp();

$app->includeFolder(TW_INC . 'ajax');
$app->includeFolder(TW_INC . 'core');
$app->includeFolder(TW_INC . 'custom');
$app->includeFolder(TW_INC . 'widgets');


App::getAssets()->add('template', [
	'deps' => ['jquery'],
	'style' => [
		'https://fonts.googleapis.com/css?family=Public+Sans:300,400,500,600,700&display=swap',
		'style.css',
	],
	'script' => [
		'scripts.js',
	],
	'localize' => function() {
		return [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ajax-nonce')
		];
	},
	'footer' => true,
	'display' => true,
	'version' => '1.0.0',
	'directory' => 'build'
]);


App::getImage()->addSizes([
	'post' => [
		'label' => 'Category',
		'width' => 240,
		'height' => 180,
		'thumb' => true,
		'crop' => ['center', 'center']
	],
	'slide' => [
		'width' => 500,
		'height' => 360,
		'hidden' => true
	]
]);


$app->addSupport([
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


$app->registerMenu([
	'main' => 'Main Menu'
]);


$app->registerType('block_set', [
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

$app->registerType('review', [
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


$app->registerTaxonomy('review_category', ['review'], [
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


$app->registerSidebar([
	'name' => 'Footer',
	'id' => 'footer',
	'description' => '',
	'before_widget' => '<div class="item widget %2$s">',
	'after_widget' => '</div>',
	'before_title' => '<div class="title">',
	'after_title' => '</div>'
]);


$app->registerWidget('Posts');