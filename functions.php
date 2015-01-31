<?php

$tw_settings = array(
		
	'menu' => array(
		'main' => 'Главное меню сайта',
	),

	'thumbs' => array(
		'post' => array(
			'width' => 262,
			'height' => 211,
			'thumb' => true,
			'crop' => array('center', 'center')
		),
		'slide' => array(
			'width' => 500,
			'height' => 360
		),
	),
	
	'scripts' => array(
		'likes',
		'colorbox',
		'jcarousel',
		'nouislider',
		'share42',
		'scrollto'
	),
	
	'widgets' => array(
		array(
			'name' => 'Блок справа в сайдбаре',
			'id' => 'sidebar',
			'description' => 'Область для виджетов в сайдбаре',
			'before_widget' => '<div class="widget">',
			'after_widget' => '</div>',
			'before_title' => '<div class="title">',
			'after_title' => '</div>'
		),
	),
	
	'types' => array(
		'slide' => array(
			'labels' => array(
				'name' => 'Слайды',
				'singular_name' => 'slide',
				'new_item' => 'Новый слайд',
				'add_new' => 'Добавить слайд',
				'add_new_item' => 'Добавить слайд',
				'edit_item' => 'Редактироовать слайд',
				'view_item' => 'Просмотреть слайд',
				'all_items' => 'Все слайды',
				'search_items' => 'Искать слайд',
				'not_found' =>	'Слайд не найден',
				'not_found_in_trash' => 'Слайд не найден в корзине',
			),
			'description' => 'Слайды на главной',
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => true,
			'show_in_nav_menus' => false,
			'show_in_admin_bar' => true,
			'menu_position' => 10,
			'menu_icon' => 'dashicons-format-image', // https://developer.wordpress.org/resource/dashicons/
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'custom-fields', 'comments', 'page-attributes'),
			'has_archive' => false,
			'rewrite' => array('slug' => 'slide', 'with_front' => false),
			'query_var' => true, 
		),
	),
	
	'navigation' => array(
		'prev' => '&#9668;',
		'next' => '&#9658;',
		'first' => false,
		'last' => false,
	),

	'acf' => array(
		'require_acf' => false,
		'json_enable' => true,
		'option_page' => false,
		'include_subcats' => true,
	),

	'init' => array(
		'ajax_posts' => false,
		'ajax_rating' => false,
		'ajax_comments' => false,
		'get_posts_filter' => false,
		'menu_active_class' => true,
		'widget_posts' => false,
		'widget_comments' => false,
		'fix_russian_date' => true,
		'fix_english_date' => true,
	)

);

$dir = get_template_directory() . '/library/';

include_once($dir . 'init.php');
include_once($dir . 'common.php');
include_once($dir . 'taxonomy.php');
include_once($dir . 'comment.php');
include_once($dir . 'widget.php');

include_once($dir . 'acf.php');
include_once($dir . 'ajax.php');
include_once($dir . 'action.php');

?>