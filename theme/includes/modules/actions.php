<?php
/**
 * A set of additional filters and hooks to modify the default WordPress behaviour
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Add an 'active' class for the selected item in menu
 */

if (tw_get_setting('modules', 'actions', 'menu_active')) {

	add_filter('nav_menu_css_class', 'tw_filter_menu_active', 10, 1);

	function tw_filter_menu_active($classes) {

		$active_classes = array(
			'current-menu-item',
			'current-menu-ancestor',
			'current-post-ancestor',
			'current-page-ancestor',
			'current-category-ancestor',
		);

		foreach ($active_classes as $class) {

			if (in_array($class, $classes)) {
				$classes[] = 'active';
				break;
			}

		}

		if (in_array('menu-item-has-children', $classes)) {
			$classes[] = 'submenu';
		}

		return $classes;

	}

}


/**
 * Clean menu items from the additional classes
 */

if (tw_get_setting('modules', 'actions', 'menu_clean')) {

	add_filter('nav_menu_css_class', 'tw_filter_menu_clean', 20);

	function tw_filter_menu_clean($classes) {

		$new_classes = array();

		foreach ($classes as $class) {

			if (strpos($class, 'menu-item') !== 0 and strpos($class, 'current-') !== 0) {
				$new_classes[] = $class;
			}

		}

		return $new_classes;

	}

}


/**
 * Clean the header from some unnecessary meta-tags and scripts
 */

if (!is_admin() and tw_get_setting('modules', 'actions', 'clean_header')) {

	add_action('after_setup_theme', 'tw_action_clean_header', 20);

	function tw_action_clean_header() {

		remove_action('wp_head', 'wp_generator');
		remove_action('wp_head', 'rsd_link');
		remove_action('wp_head', 'feed_links', 2);
		remove_action('wp_head', 'feed_links_extra', 3);
		remove_action('wp_head', 'wc_products_rss_feed', 10);
		remove_action('wp_head', 'wlwmanifest_link');
		remove_action('wp_head', 'wp_shortlink_wp_head', 10);
		remove_action('wp_head', 'wp_oembed_add_host_js');
		remove_action('wp_head', 'wp_oembed_add_discovery_links');
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_head', 'rest_output_link_wp_head', 10);
		remove_action('wp_print_styles', 'print_emoji_styles');

		add_filter('the_generator', '__return_false');

	}


	add_filter('wp_default_scripts', 'tw_filter_remove_migrate');

	function tw_filter_remove_migrate($scripts) {

		if ($scripts instanceof WP_Scripts) {

			$scripts->remove('jquery');
			$scripts->add('jquery', false, array('jquery-core'));

		}

		return $scripts;

	}

}


/**
 * Add the max-width property to the default caption shortcode and fix its width
 */

if (tw_get_setting('modules', 'actions', 'caption_responsive')) {

	add_filter('img_caption_shortcode', 'tw_filter_resonsive_caption', 10, 3);

	function tw_filter_resonsive_caption($value = false, $attr = array(), $content = '') {

		$atts = shortcode_atts(array(
			'id' => '',
			'align' => 'alignnone',
			'width' => '',
			'caption' => '',
			'class' => '',
		), $attr, 'caption');

		$atts['width'] = intval($atts['width']);

		if ($atts['width'] < 1 or empty($atts['caption'])) {
			return $content;
		}

		if (!empty($atts['id'])) {
			$atts['id'] = 'id="' . esc_attr(sanitize_html_class($atts['id'])) . '" ';
		}

		$atts['class'] = 'class="' . trim('wp-caption ' . $atts['align'] . ' ' . $atts['class']) . '" ';

		$style = 'style="max-width: ' . ($atts['width'] + intval(tw_get_setting('modules', 'actions', 'caption_padding'))) . 'px;"';

		return '<div ' . $atts['id'] . $atts['class'] . $style . '>' . do_shortcode($content) . '<p class="wp-caption-text">' . $atts['caption'] . '</p></div>';

	}

}


/**
 * Set the default image compression quality
 */

if (tw_get_setting('modules', 'actions', 'image_quality')) {

	add_action('jpeg_quality', 'tw_action_image_quality');

	function tw_action_image_quality() {

		$quality = intval(tw_get_setting('modules', 'actions', 'image_quality'));

		if ($quality <= 0 or $quality > 100) {
			$quality = 82;
		}

		echo $quality;

		return $quality;

	}

}


/**
 * Set the default image compression quality
 */

if (tw_get_setting('modules', 'actions', 'exclude_medium')) {

	add_filter('intermediate_image_sizes', 'tw_filter_exclude_medium');

	function tw_filter_exclude_medium($sizes) {

		return array_diff($sizes, ['medium_large']);

	}

}


/**
 * Remove some WordPress inline styles for the admin bar
 */

if (tw_get_setting('modules', 'actions', 'fixed_header')) {

	add_action('get_header', 'tw_action_fixed_header');

	function tw_action_fixed_header() {

		remove_action('wp_head', '_admin_bar_bump_cb');
	}

}


/**
 * Enqueue comment reply script for threaded comments
 */

if (tw_get_setting('modules', 'actions', 'comment_reply')) {

	add_action('wp_enqueue_scripts', 'tw_action_comment_reply');

	function tw_action_comment_reply() {

		if (is_singular() and comments_open() and get_option('thread_comments')) {
			wp_enqueue_script('comment-reply');
		}

	}

}