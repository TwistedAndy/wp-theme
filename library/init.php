<?php

/*
Описание: библиотека для инициализации темы
Автор: Тониевич Андрей
Версия: 1.9
Дата: 21.04.2016
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


if (tw_settings('taxonomies')) {

	add_action('init', 'tw_taxonomies');

	function tw_taxonomies() {

		$taxonomies = tw_settings('taxonomies');

		foreach ($taxonomies as $taxonomy) {

			register_taxonomy($taxonomy['name'], $taxonomy['types'], $taxonomy['args']);

		}

	}

}


if (tw_settings('scripts')) {

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
				'styles' => array('social-likes.css'),
				'scripts' => array('social-likes.min.js'),
			),
			'colorbox' => array(
				'styles' => array('colorbox/colorbox.css'),
				'scripts' => array('jquery.colorbox-min.js'),
			),
			'styler' => array(
				'styles' => array('jquery.formstyler.css'),
				'scripts' => array('jquery.formstyler.min.js'),
			),
			'jcarousel' => array(
				'scripts' => array('jquery.jcarousel.min.js'),
			),
			'nivo' => array(
				'styles' => array('nivo-slider.css'),
				'scripts' => array('jquery.nivo.slider.pack.js'),
			)
		);

		$scripts = tw_settings('scripts');

		$dir = get_template_directory_uri() . '/scripts/';

		foreach ($scripts as $script => $config) {

			if (is_bool($config) and $config) {

				if (!empty($predefined_scripts[$script])) {
					$config = $predefined_scripts[$script];
					$config['display'] = true;
				} elseif (wp_script_is($script, 'registered')) {
					wp_enqueue_script($script);
				} elseif (wp_style_is($script, 'registered')) {
					wp_enqueue_style($script);
				}

			}

			if (is_array($config)) {

				$config = wp_parse_args($config, $defaults);

				if ($config['display']) {

					if (!empty($config['scripts']) and is_array($config['scripts'])) {
						foreach ($config['scripts'] as $file) {
							wp_register_script($script, $dir . $file, $config['deps'], null);
							wp_enqueue_script($script);
						}
					}

					if (!empty($config['styles']) and is_array($config['styles'])) {
						foreach ($config['styles'] as $file) {
							wp_register_style($script, $dir . $file, array(), null);
							wp_enqueue_style($script);
						}
					}

				}

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
