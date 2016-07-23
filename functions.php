<?php

$tw_settings = array(

	'menu' => array(
		'main' => 'Главное меню сайта',
	),

	'thumbs' => array(
		'post' => array(
			'width' => 240,
			'height' => 180,
			'thumb' => true,
			'crop' => array('center', 'center')
		),
		'slide' => array(
			'width' => 500,
			'height' => 360
		),
	),

	'assets' => array(
		'template' => array(
			'deps' => array('jquery'),
			'style' => 'css/style.css',
			'script' => 'assets/theme.js',
			'footer' => true,
			'localize' => array(
				'ajaxurl' => admin_url('admin-ajax.php')
			)
		),
		'nivo' => false,
		'likes' => false,
		'styler' => false,
		'colorbox' => false,
		'jcarousel' => false,
	),

	'ajax' => array(
		'email' => false,
		'rating' => false,
		'posts' => false,
		'comments' => false
	),

	'sidebars' => array(
		array(
			'name' => 'Блок справа в сайдбаре',
			'id' => 'sidebar',
			'description' => 'Область для виджетов в сайдбаре',
			'before_widget' => '<div class="widget %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<div class="title">',
			'after_title' => '</div>'
		),
	),

	'widgets' => array(
		'posts' => false,
		'comments' => false
	),

	'taxonomies' => array(
		array(
			'name' => 'receipts',
			'types' => array('receipt'),
			'args' => array(
				'label' => '',
				'labels' => array(
					'name' => 'Категории рецептов',
					'singular_name' => 'Категория рецепта',
					'search_items' => 'Поиск категорий',
					'all_items' => 'Все категории',
					'parent_item' => 'Родительская категория',
					'parent_item_colon' => 'Родительская категория:',
					'edit_item' => 'Редактировать категорию рецепта',
					'update_item' => 'Обновить категорию',
					'add_new_item' => 'Новая категория',
					'new_item_name' => 'Имя категория',
					'menu_name' => 'Категории рецептов',
				),
				'query_var'	=> true,
				'public' => true,
				'show_in_nav_menus' => true,
				'show_ui'  => true,
				'show_tagcloud' => true,
				'hierarchical' => true,
				'update_count_callback' => '',
				'rewrite' => array('slug' => 'receipts', 'with_front' => false, 'hierarchical' => true),
				'show_admin_column' => false,
				'show_in_quick_edit' => null,
			)
		),
	),

	'types' => array(
		'receipt' => array(
			'labels' => array(
				'name' => 'Рецепты',
				'singular_name' => 'Рецепт',
				'new_item' => 'Новый рецепт',
				'add_new' => 'Добавить рецепт',
				'add_new_item' => 'Добавить рецепт',
				'edit_item' => 'Редактироовать рецепт',
				'view_item' => 'Просмотреть рецепт',
				'all_items' => 'Все рецепты',
				'search_items' => 'Искать рецепты',
				'not_found' =>	'Рецепт не найден',
				'not_found_in_trash' => 'Рецепт не найден в корзине',
			),
			'description' => 'Каталог рецептов сайта',
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'show_in_nav_menus' => true,
			'show_in_admin_bar' => true,
			'menu_position' => 10,
			'menu_icon' => 'dashicons-heart', /* https://developer.wordpress.org/resource/dashicons/ */
			'hierarchical' => false,
			'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'custom-fields', 'comments'),
			'has_archive' => true,
			'rewrite' => array('slug' => 'recipes', 'with_front' => false, 'hierarchical' => false),
			'query_var' => true,
			'taxonomies' => array('receipts')
		),
	),

	'pagination' => array(
		'prev' => '&#9668;',
		'next' => '&#9658;',
		'first' => false,
		'last' => false,
	),

	'acf' => array(
		'require_acf' => false,
		'json_enable' => true,
		'option_page' => false,
		'category_rules' => true,
		'include_subcats' => true,
	),

	'init' => array(
		'action_get_posts' => false,
		'action_menu_active' => true,
		'action_fix_caption' => true,
		'action_clean_header' => true,
		'module_cyrtolat' => true,
		'module_russian_date' => true,
		'module_english_date' => true,
	)

);

include_once(dirname(__FILE__) . '/library/init.php');