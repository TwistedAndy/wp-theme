<?php
/**
 * Theme initialization script
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */

define('TW_INC', dirname(__DIR__));
define('TW_ROOT', dirname(TW_INC));

include_once TW_INC . '/core/loader.php';


/**
 * Main theme configuration
 */

add_action('after_setup_theme', 'tw_action_setup');

function tw_action_setup() {

	/**
	 * Add support for the title tag and HTML5 galleries
	 */

	add_theme_support('title-tag');

	add_theme_support('html5', array('gallery'));


	/**
	 * Load translations for the theme
	 */

	load_theme_textdomain('wp-theme', TW_ROOT . '/languages');


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

		$sizes = tw_get_setting('thumbs');

		if (is_array($sizes)) {

			foreach ($sizes as $name => $size) {

				if (empty($size['hidden'])) {

					$crop = (isset($size['crop'])) ? $size['crop'] : true;

					if (!isset($size['width'])) {
						$size['width'] = 0;
					}

					if (!isset($size['height'])) {
						$size['height'] = 0;
					}

					if (in_array($name, array('thumbnail', 'medium', 'medium_large', 'large'))) {

						if (get_option($name . '_size_w') != $size['width']) {
							update_option($name . '_size_w', $size['width']);
						}

						if (get_option($name . '_size_h') != $size['height']) {
							update_option($name . '_size_h', $size['height']);
						}

						if (isset($size['crop']) and get_option($name . '_crop') != $crop) {
							update_option($name . '_crop', $crop);
						}

					} else {

						add_image_size($name, $size['width'], $size['height'], $crop);

					}

					if (isset($size['thumb']) and $size['thumb']) {

						set_post_thumbnail_size($size['width'], $size['height'], $crop);

					}

				}

			}

		}

	}

}


/**
 * Add the custom image sizes to the media editor
 */

if (tw_get_setting('thumbs')) {

	add_filter('image_size_names_choose', 'tw_filter_editor_image_sizes');

	function tw_filter_editor_image_sizes($sizes) {

		$theme_sizes = tw_get_setting('thumbs');

		if (is_array($theme_sizes)) {

			$array = array();

			foreach ($theme_sizes as $name => $size) {

				if (empty($size['hidden'])) {

					if (!empty($size['label'])) {
						$label = $size['label'];
					} else {
						$label = ucfirst($name);
					}

					$array[$name] = $label;

				}

			}

			$sizes = array_merge($sizes, $array);

		}

		return $sizes;

	}

}


/**
 * Register the custom editor styles
 */

if (tw_get_setting('styles')) {

	add_filter('tiny_mce_before_init', 'tw_filter_editor_styles');

	function tw_filter_editor_styles($array) {

		$style_formats = tw_get_setting('styles');

		if (!empty($array['style_formats'])) {

			$existing_formats = json_decode($array['style_formats'], true);

			if (is_array($existing_formats)) {

				$style_formats = array_merge($existing_formats, $style_formats);

			}

		}

		$array['style_formats'] = json_encode($style_formats);

		return $array;

	}


	add_filter('mce_buttons_2', 'tw_filter_editor_format_button');

	function tw_filter_editor_format_button($buttons) {

		array_unshift($buttons, 'styleselect');

		return $buttons;

	}


	if (file_exists(TW_ROOT . '/editor-style.css')) {

		add_action('init', 'tw_action_editor_stylesheet');

		function tw_action_editor_stylesheet() {

			add_editor_style('editor-style.css');

		}

	}

}


/**
 * Register the custom post types
 */

if (tw_get_setting('types')) {

	add_action('init', 'tw_action_post_types');

	function tw_action_post_types() {

		$types = tw_get_setting('types');

		if (is_array($types)) {

			foreach ($types as $name => $type) {

				register_post_type($name, $type);

			}

		}

	}

}


/**
 * Register the custom taxonomies
 */

if (tw_get_setting('taxonomies')) {

	add_action('init', 'tw_action_taxonomies');

	function tw_action_taxonomies() {

		$taxonomies = tw_get_setting('taxonomies');

		if (is_array($taxonomies)) {

			foreach ($taxonomies as $taxonomy) {

				register_taxonomy($taxonomy['name'], $taxonomy['types'], $taxonomy['args']);

			}

		}

	}

}


/**
 * Register the custom sidebars
 */

if (tw_get_setting('sidebars')) {

	add_action('widgets_init', 'tw_action_sidebars');

	function tw_action_sidebars() {

		$sidebars = tw_get_setting('sidebars');

		if (is_array($sidebars)) {

			foreach ($sidebars as $sidebar) {

				register_sidebar($sidebar);

			}

		}

	}

}


/**
 * Register and include the custom widgets
 */

if (tw_get_setting('widgets')) {

	add_action('widgets_init', 'tw_action_widgets');

	function tw_action_widgets() {

		$widgets = tw_get_setting('widgets');

		if (is_array($widgets)) {

			foreach ($widgets as $file => $active) {

				if ($active) {

					$filename = TW_INC . '/widgets/' . $file . '.php';

					if (is_file($filename)) {

						include_once($filename);

						register_widget('twisted_widget_' . $file);

					}

				}

			}

		}

	}

}




