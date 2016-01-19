<?php

/*
Описание: библиотека для инициализации темы
Автор: Тониевич Андрей
Версия: 1.7
Дата: 19.01.2016
*/

function tw_settings($group = false, $name = false) {
	
	global $tw_settings;
	
	if ($name and $group and isset($tw_settings[$group][$name])) {
		return $tw_settings[$group][$name];
	} elseif ($group and isset($tw_settings[$group])) {
		return $tw_settings[$group];
	} elseif ($name == false and $group == false) {
		return $tw_settings;
	} else {
		return false;
	}
	
};


add_action('after_setup_theme', 'tw_setup');

function tw_setup() {

	add_theme_support('title-tag');
	
	if (tw_settings('menu')) {

		register_nav_menus(tw_settings('menu'));

	}
	
	if (tw_settings('thumbs')) {

		add_theme_support('post-thumbnails');
		
		foreach (tw_settings('thumbs') as $name => $thumb) {
	
			$crop = (isset($thumb['crop'])) ? $thumb['crop'] : true;
			
			if (!isset($thumb['width'])) $thumb['width'] = 0;
			
			if (!isset($thumb['height'])) $thumb['height'] = 0;
			
			if (in_array($name, array('thumbnail', 'medium', 'large'))) {
				
				if (get_option($name . '_size_w') != $thumb['width']) {
					update_option($name . '_size_w', $thumb['width']);
				}
				
				if (get_option($name . '_size_h') != $thumb['height']) {
					update_option($name . '_size_h', $thumb['height']);
				}
				
				if (isset($thumb['crop']) and get_option($name . '_crop') != $crop) {
					update_option($name . '_crop', $crop);
				}
				
			} else {
		
				add_image_size($name, $thumb['width'], $thumb['height'], $crop);
	
			}
			
			if (isset($thumb['thumb']) and $thumb['thumb']) {
				
				set_post_thumbnail_size($thumb['width'], $thumb['height'], $crop);
			
			}
			
		}
	
	}	
	
}


if (tw_settings('types')) {

	add_action('init', 'tw_post_type');
	
	function tw_post_type() {
		
		$types = tw_settings('types');
		
		foreach ($types as $name => $type) {
		
			register_post_type($name, $type);
		
		}
		
	}

}


if (tw_settings('scripts')) {

	add_action('init', 'tw_register_scripts');
	
	function tw_register_scripts() {
		
		$scripts = array(
			'likes'			=> 'social-likes.min.js',
			'share42'		=> 'share42/share42.js',
			'jcarousel' 	=> 'jquery.jcarousel.min.js',
			'colorbox'		=> 'jquery.colorbox-min.js',
			'scrollto'		=> 'jquery.scrollTo.min.js',
			'nouislider'	=> 'jquery.nouislider.min.js',
			'scrollpane'	=> 'jquery.jscrollpane.min.js',
			'mousewheel'	=> 'jquery.mousewheel.js',
			'formstyler'	=> 'jquery.formstyler.min.js',
		);
		
		$stylesheets = array(
			'likes'			=> 'social-likes.css',
			'colorbox'		=> 'colorbox/colorbox.css',
			'nouislider'	=> 'jquery.nouislider.css',
		);
		
		$dir = get_template_directory_uri() . '/scripts/';
			
		foreach (tw_settings('scripts') as $script) {
			
			if (isset($scripts[$script])) {
				wp_register_script($script, $dir . $scripts[$script], array('jquery'), null);
				wp_enqueue_script($script);
			} elseif (wp_script_is($script) or $script == 'jquery') {
				wp_enqueue_script($script);
			}
			
			if (isset($stylesheets[$script])) {
				wp_register_style($script, $dir . $stylesheets[$script]);
				wp_enqueue_style($script);
			}
			
		}
			
	}

}


if (tw_settings('widgets')) {

	add_action('widgets_init', 'tw_widgets_init');
	
	function tw_widgets_init() {

		foreach (tw_settings('widgets') as $widget) {
		
			register_sidebar($widget);

		}
		
	}

}


$dir = get_template_directory() . '/library/';

include_once($dir . 'common.php');
include_once($dir . 'taxonomy.php');
include_once($dir . 'comment.php');
include_once($dir . 'widgets.php');

include_once($dir . 'acf.php');
include_once($dir . 'ajax.php');
include_once($dir . 'actions.php');
include_once($dir . 'modules.php');

?>
