<?php
/**
 * Theme initialization script
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */

define('TW_ROOT', dirname(__DIR__));
define('TW_INC', __DIR__);


/**
 * Include all necessary files
 */

$files = array(
	'assets',
	'content',
	'settings',
	'taxonomy',
	'thumbs',
	'widget'
);

foreach ($files as $file) {

	$filename = TW_INC . '/core/' . $file . '.php';

	if (is_file($filename)) {
		include_once($filename);
	}

}


/**
 * Include enabled modules
 */

if (tw_get_setting('modules')) {

	$modules = tw_get_setting('modules');

	foreach ($modules as $file => $enabled) {

		if ($enabled) {

			$filename = TW_INC . '/modules/' . $file . '.php';

			if (is_file($filename)) {
				include_once($filename);
			}

		}

	}

}


/**
 * Main theme configuration
 */

add_action('after_setup_theme', 'tw_setup');

function tw_setup() {

	/**
	 * Add support for title tag and HTML5 galleries
	 */

	add_theme_support('title-tag');

	add_theme_support('html5', array('gallery'));


	/**
	 * Load translations for the theme
	 */

	load_theme_textdomain('wp-theme', get_template_directory() . '/languages');


	/**
	 * Register menu locations
	 */

	if (tw_get_setting('menu')) {

		register_nav_menus(tw_get_setting('menu'));

	}

	/**
	 * Register custom image sizes
	 */

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


/**
 * Register custom editor styles
 */

if (tw_get_setting('styles')) {

	add_filter('tiny_mce_before_init', 'tw_register_styles');

	function tw_register_styles($array) {

		$style_formats = tw_get_setting('styles');

		$array['style_formats'] = json_encode($style_formats);

		return $array;

	}


	add_filter('mce_buttons_2', 'tw_enable_fromat_button');

	function tw_enable_fromat_button($buttons) {

		array_unshift($buttons, 'styleselect');

		return $buttons;

	}


	if (file_exists(TW_ROOT . '/editor-style.css')) {

		add_action('init', 'tw_add_editor_styles');

		function tw_add_editor_styles() {
			add_editor_style('editor-style.css');
		}

	}

}


/**
 * Register custom post types
 */

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


/**
 * Register custom taxonomies
 */

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


/**
 * Register custom sidebars
 */

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


/**
 * Register and include custom widgets
 */

if (tw_get_setting('widgets')) {

	add_action('widgets_init', 'tw_widgets_init');

	function tw_widgets_init() {

		$widgets = tw_get_setting('widgets');

		if (is_array($widgets)) {

			foreach ($widgets as $widget => $active) {

				$file = TW_INC . '/widgets/' . strtolower($widget) . '.php';

				if ($active and is_file($file)) {

					include_once($file);

					register_widget('twisted_widget_' . $widget);

				}

			}

		}

	}

}


/**
 * Register and include custom ajax handlers
 */

if (tw_get_setting('ajax')) {

	$ajax_handlers = tw_get_setting('ajax');

	if (is_array($ajax_handlers) and $ajax_handlers) {

		foreach ($ajax_handlers as $handler => $active) {

			$file = TW_INC . '/ajax/' . strtolower($handler) . '.php';

			if ($active and is_file($file)) {

				include_once($file);

			}

		}

	}

}







