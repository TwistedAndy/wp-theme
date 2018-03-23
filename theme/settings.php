<?php

$settings['menu'] = array(
	'main' => 'Главное меню',
);


$settings['thumbs'] = array(
	'post' => array(
		'label' => 'Рубрика',
		'width' => 240,
		'height' => 180,
		'thumb' => true,
		'crop' => array('center', 'center')
	),
	'slide' => array(
		'width' => 500,
		'height' => 360,
		'hidden' => true
	)
);


$settings['assets'] = array(
	'template' => array(
		'deps' => array('jquery'),
		'style' => 'css/style.css',
		'script' => 'scripts/theme.js',
		'footer' => true,
		'localize' => array(
			'ajaxurl' => admin_url('admin-ajax.php')
		),
		'display' => true
	),
	'nivo' => false,
	'styler' => false,
	'fancybox' => false,
	'flickity' => false,
);


$settings['sidebars'] = array(
	array(
		'name' => 'Сайдбар',
		'id' => 'sidebar',
		'description' => 'Область для виджетов сбоку',
		'before_widget' => '<div class="widget %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<div class="title">',
		'after_title' => '</div>'
	),
);


$settings['types'] = array(
	'project' => array(
		'labels' => array(
			'name' => 'Работы',
			'singular_name' => 'Работа',
			'new_item' => 'Новая работа',
			'add_new' => 'Добавить работу',
			'add_new_item' => 'Добавить работу',
			'edit_item' => 'Редактироовать работу',
			'view_item' => 'Просмотреть работу',
			'all_items' => 'Все работы',
			'search_items' => 'Искать работы',
			'not_found' => 'Работа не найдена',
			'not_found_in_trash' => 'Работа не найдена в корзине',
		),
		'description' => 'Выполненные работы',
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_in_admin_bar' => true,
		'menu_position' => 10,
		'menu_icon' => 'dashicons-camera', /* https://developer.wordpress.org/resource/dashicons/ */
		'hierarchical' => false,
		'supports' => array('title', 'editor', 'excerpt', 'author', 'thumbnail', 'custom-fields', 'comments'),
		'has_archive' => true,
		'rewrite' => array('slug' => 'projects', 'with_front' => true, 'hierarchical' => false),
		'query_var' => true,
		'taxonomies' => array('projects')
	),
);


$settings['taxonomies'] = array(
	array(
		'name' => 'projects',
		'types' => array('project'),
		'args' => array(
			'label' => '',
			'labels' => array(
				'name' => 'Категории работ',
				'singular_name' => 'Категория работы',
				'search_items' => 'Поиск категорий',
				'all_items' => 'Все категории',
				'parent_item' => 'Родительская категория',
				'parent_item_colon' => 'Родительская категория:',
				'edit_item' => 'Редактировать категорию работы',
				'update_item' => 'Обновить категорию',
				'add_new_item' => 'Новая категория',
				'new_item_name' => 'Имя категория',
				'menu_name' => 'Категории работ',
			),
			'query_var' => true,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => false,
			'hierarchical' => true,
			'update_count_callback' => '',
			'rewrite' => array('slug' => 'works', 'with_front' => true, 'hierarchical' => true),
			'show_admin_column' => true,
			'show_in_quick_edit' => null,
		)
	),
);


$settings['styles'] = array(
	array(
		'title' => 'Custom style',
		'block' => 'div',
		'classes' => 'custom_class',
		'wrapper' => true,
	),
);


$settings['ajax'] = array(
	'email' => false,
	'rating' => false,
	'posts' => false,
	'comments' => false
);


$settings['modules'] = array(
	'acf' => array(
		'require_acf' => true,
		'json_enable' => true,
		'option_page' => true,
		'category_rules' => true,
		'include_subcats' => true,
	),
	'actions' => array(
		'caption_padding' => 0,
		'menu_clean' => false,
		'menu_active' => true,
		'fix_caption' => true,
		'clean_header' => true,
		'comment_reply' => false
	),
	'breadcrumbs' => array(
		'microdata' => 'json',
		'include_archive' => false,
		'include_current' => true
	),
	'comments' => true,
	'custom' => true,
	'cyrdate' => array(
		'english_convert' => false
	),
	'blocks' => array(
		'option_field' => 'blocks_default',
		'load_default' => true,
	),
	'cyrtolat' => true,
	'pageviews' => true,
	'pagination' => array(
		'prev' => '&#9668;',
		'next' => '&#9658;',
		'first' => false,
		'last' => false,
	),
);


$settings['widgets'] = array(
	'posts' => false,
	'comments' => false
);


foreach ($settings as $group => $value) {
	tw_set_setting(false, $group, $value);
}