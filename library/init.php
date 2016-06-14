<?php

/*
Описание: библиотека для инициализации темы оформления
Автор: Тониевич Андрей
Версия: 2.0
Дата: 04.06.2016
*/

$dir = dirname(__FILE__) . '/';

include_once($dir . 'settings.php');
include_once($dir . 'common.php');
include_once($dir . 'taxonomy.php');
include_once($dir . 'comment.php');
include_once($dir . 'widget.php');

include_once($dir . 'acf.php');
include_once($dir . 'custom.php');
include_once($dir . 'modules.php');
include_once($dir . 'actions.php');


add_action('after_setup_theme', 'tw_setup');

function tw_setup() {

	add_theme_support('title-tag');

	add_theme_support('html5', array('gallery'));
	
	load_theme_textdomain('wp-theme', get_template_directory() . '/languages');

	if (tw_get_setting('menu')) {

		register_nav_menus(tw_get_setting('menu'));

	}

	if (tw_get_setting('thumbs')) {

		add_theme_support('post-thumbnails');

		$thumbs = tw_get_setting('thumbs');

		if (is_array($thumbs)) {

			foreach ($thumbs as $name => $thumb) {

				$crop = (isset($thumb['crop'])) ? $thumb['crop'] : true;

				if (!isset($thumb['width'])) {
					$thumb['width'] = 0;
				}

				if (!isset($thumb['height'])) {
					$thumb['height'] = 0;
				}

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

}


if (tw_get_setting('types')) {

	add_action('init', 'tw_post_type');

	function tw_post_type() {

		$types = tw_get_setting('types');

		if (is_array($types)) {

			foreach ($types as $name => $type) {

				register_post_type($name, $type);

			}

		}

	}

}


if (tw_get_setting('taxonomies')) {

	add_action('init', 'tw_taxonomies');

	function tw_taxonomies() {

		$taxonomies = tw_get_setting('taxonomies');

		if (is_array($taxonomies)) {

			foreach ($taxonomies as $taxonomy) {

				register_taxonomy($taxonomy['name'], $taxonomy['types'], $taxonomy['args']);

			}

		}

	}

}


if (tw_get_setting('scripts')) {

	add_action('init', 'tw_register_scripts');

	function tw_register_scripts() {

		$defaults = array(
			'deps' => array('jquery'),
			'styles' => array(),
			'scripts' => array(),
			'display' => false
		);

		$predefined_scripts = array(
			'likes' => array(
				'styles' => 'social-likes.css',
				'scripts' => 'social-likes.min.js',
			),
			'colorbox' => array(
				'styles' => 'colorbox/colorbox.css',
				'scripts' => 'jquery.colorbox-min.js',
			),
			'styler' => array(
				'styles' => 'jquery.formstyler.css',
				'scripts' => 'jquery.formstyler.min.js',
			),
			'jcarousel' => array(
				'scripts' => 'jquery.jcarousel.min.js',
			),
			'nivo' => array(
				'styles' => 'nivo-slider.css',
				'scripts' => 'jquery.nivo.slider.pack.js',
			)
		);

		$scripts = tw_get_setting('scripts');

		$dir = get_template_directory_uri() . '/scripts/';

		if (!empty($scripts) and is_array($scripts)) {

			foreach ($scripts as $script => $config) {

				if (is_callable($config)) {
					$config = $config();
				}

				if (is_bool($config) and $config) {

					$config = array();

					$config['display'] = true;

					if (wp_script_is($script, 'registered')) {
						wp_enqueue_script($script);
					}

					if (wp_style_is($script, 'registered')) {
						wp_enqueue_style($script);
					}

				}

				if (!empty($predefined_scripts[$script])) {
					$config = wp_parse_args($config, $predefined_scripts[$script]);
				}

				if (is_array($config)) {

					$config = wp_parse_args($config, $defaults);

					if (!empty($config['scripts']) and is_string($config['scripts'])) {
						wp_register_script($script, $dir . $config['scripts'], $config['deps'], null);
						if ($config['display']) {
							wp_enqueue_script($script);
						}
					}

					if (!empty($config['styles']) and is_string($config['styles'])) {
						wp_register_style($script, $dir . $config['styles'], array(), null);
						if ($config['display']) {
							wp_enqueue_style($script);
						}
					}

				}

			}

		}

	}

}


if (tw_get_setting('sidebars')) {

	add_action('widgets_init', 'tw_sidebars_init');

	function tw_sidebars_init() {

		$sidebars = tw_get_setting('sidebars');

		if (is_array($sidebars)) {

			foreach ($sidebars as $sidebar) {

				register_sidebar($sidebar);

			}

		}

	}

}


if (tw_get_setting('widgets')) {

	add_action('widgets_init', 'tw_widgets_init');

	function tw_widgets_init() {

		$widgets = tw_get_setting('widgets');

		if (is_array($widgets)) {

			$dir = dirname(__FILE__);

			foreach ($widgets as $widget => $active) {

				$file = $dir . '/widgets/' . strtolower($widget) . '.php';

				if ($active and is_file($file)) {

					include_once($file);

					register_widget('twisted_widget_' . $widget);

				}

			}

		}

	}

}


if (tw_get_setting('widgets')) {

	$ajax_handlers = tw_get_setting('ajax');

	if (is_array($ajax_handlers) and $ajax_handlers) {

		$dir = dirname(__FILE__);

		foreach ($ajax_handlers as $handler => $active) {

			$file = $dir . '/ajax/' . strtolower($handler) . '.php';

			if ($active and is_file($file)) {

				include_once($file);

			}

		}

	}

}







