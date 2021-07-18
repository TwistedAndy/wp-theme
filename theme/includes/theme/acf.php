<?php
/**
 * Advanced Custom Fields library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */


/**
 * Increase content width on a term edit screen
 */
add_action('admin_head', function() { ?>

	<style>
		#edittag {
			max-width: 1920px;
		}

		.acf-repeater.-table.-empty .acf-table {
			display: none;
		}
	</style>

<?php });


/**
 * Add new options page for the theme settings
 */
add_action('init', 'tw_filter_options_page');

function tw_filter_options_page() {

	if (function_exists('acf_add_options_page')) {

		acf_add_options_page([
			'page_title' => __('Theme Settings', 'twee'),
			'menu_title' => __('Theme Settings', 'twee'),
			'menu_slug' => 'theme-settings',
			'capability' => 'manage_options',
			'redirect' => false,
			'position' => 90,
			'icon_url' => 'dashicons-star-filled',
			'update_button' => __('Refresh', 'twee'),
			'autoload' => true
		]);

	}

}


/**
 * Save the ACF field groups to JSON files
 */
add_filter('acf/settings/save_json', 'tw_filter_json_save');

function tw_filter_json_save() {

	$path = TW_INC . 'acf';

	if (!is_dir($path)) {
		mkdir($path, 0755);
	}

	return $path;

}


/**
 * Load the ACF field groups from JSON files
 */
add_filter('acf/settings/load_json', 'tw_filter_json_load');

function tw_filter_json_load($paths) {

	unset($paths[0]);

	$path = TW_INC . 'acf';

	if (!is_dir($path)) {
		mkdir($path, 0755);
	}

	$paths[] = $path;

	return $paths;

}


/**
 * Add an API key for the Google Maps field
 */
add_filter('acf/settings/google_api_key', 'tw_filter_google_api');

function tw_filter_google_api() {
	return 'AIzaSyAJ5QTsj4apSnVK-6T7HMQfUW5-RljJTQ4';
}


/**
 * A fallback function for the ACF plugin
 */
if (!function_exists('get_field') and !is_admin()) {

	function get_field($field, $post_id = false) {

		$value = false;

		if (is_numeric($post_id)) {

			if (empty($post_id)) {
				$post_id = intval(get_the_ID());
			}

			$value = get_post_meta($post_id, $field, true);

		} elseif ($post_id instanceof WP_Term) {

			$value = get_term_meta($post_id->term_id, $field, true);

		} elseif ($post_id instanceof WP_User) {

			$value = get_user_meta($post_id->ID, $field, true);

		} elseif ($post_id == 'option' or $post_id == 'options') {

			$value = get_option($field);

		} elseif (strpos($post_id, '_') !== false) {

			$parts = explode('_', $post_id);

			if ($parts[0] == 'category' or taxonomy_exists($parts[0])) {
				$value = get_term_meta($parts[1], $field, true);
			} elseif ($parts[0] == 'user') {
				$value = get_user_meta($parts[1], $field, true);
			}

		}

		return $value;

	}

}