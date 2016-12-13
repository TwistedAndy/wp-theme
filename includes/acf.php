<?php
/**
 * A set of functions to interact with the Advanced Custom Fields plugin
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.8
 */


/**
 * Retrieve the current page id for the get_field() function
 *
 * @return string ID of the current page
 */

function tw_acf_get_current_id() {

	$post_id = false;

	if (is_category()) {

		$post_id = 'category_' . get_query_var('cat');

	} elseif (is_tag() or is_tax()) {

		$queried_object = get_queried_object();

		if (!empty($queried_object->taxonomy) and !empty($queried_object->term_id)) {

			$post_id = $queried_object->taxonomy . '_' . $queried_object->term_id;

		}

	} elseif (is_singular()) {

		$post_id = get_the_ID();

	}

	return $post_id;

}


/**
 * Add new options page for the theme settings
 */

if (tw_get_setting('acf', 'option_page') and function_exists('acf_add_options_page')) {

	acf_add_options_page(array(
		'page_title' => __('Edit the theme settings', 'wp-theme'),
		'menu_title' => __('Theme settings', 'wp-theme'),
		'menu_slug' => 'theme-settings',
		'capability' => 'manage_options',
		'redirect' => false,
		'position' => 90,
		'icon_url' => 'dashicons-star-filled',
		'update_button' => __('Refresh', 'wp-theme'),
		'autoload' => true
	));

}


/**
 * Check the subcategories too when displaying the fields for the specified category
 */

if (tw_get_setting('acf', 'include_subcats')) {

	add_filter('acf/location/rule_match/post_category', 'tw_match_subcategories', 10, 3);

	function tw_match_subcategories($match, $rule, $options) {

		if (in_array($rule['operator'], array('==', '!=')) and strpos($rule['value'], 'category:') === 0) {

			$category = get_category_by_slug(urldecode(str_replace('category:', '', $rule['value'])));

			if ($category and $post_id = intval($options['post_id'])) {

				$category_id = $category->cat_ID;

				if ($options['ajax']) {
					$categories = $options['post_taxonomy'];
				} else {
					$categories = tw_post_categories($post_id, true, true);
				}

				if ($rule['operator'] == '==') $return = true; else $return = false;

				if (is_array($categories) and $categories) {
					if (in_array($category_id, $categories)) {
						return $return;
					} else {
						foreach ($categories as $category) {
							if ($parents = get_ancestors($category, 'category')) {
								if (in_array($category_id, $parents)) return $return;
							}
						}
					}
				}

			}

		}

		return $match;

	}

}


/**
 * Add a set of additional rules to display the fields only in the selected category or subcategories
 */

if (tw_get_setting('acf', 'category_rules')) {

	add_filter('acf/location/rule_types', 'tw_acf_location_rules_types');

	function tw_acf_location_rules_types($choices) {

		$choices[__('Taxonomy', 'wp-theme')] = array(
			'tax_category' => __('Category', 'wp-theme'),
			'tax_category_sub' => __('Subcategories', 'wp-theme'),
			'tax_category_all' => __('Category and subcategories', 'wp-theme'),
		);

		return $choices;

	}

	add_filter('acf/location/rule_values/tax_category', 'tw_acf_location_rules_values_category');
	add_filter('acf/location/rule_values/tax_category_all', 'tw_acf_location_rules_values_category');
	add_filter('acf/location/rule_values/tax_category_sub', 'tw_acf_location_rules_values_category');

	function tw_acf_location_rules_values_category($choices) {

		$categories = get_categories(array(
			'hide_empty' => false,
			'taxonomy' => 'category',
		));

		$categories = _get_term_children(0, $categories, 'category');

		if ($categories) {
			foreach ($categories as $category) {
				$ancestors = get_ancestors($category->term_id, 'category');
				$title = str_repeat('- ', count($ancestors)) . $category->name;
				$choices[$category->term_id] = $title;
			}
		}

		return $choices;
	}

	add_filter('acf/location/rule_match/tax_category', 'tw_acf_location_rules_match_category', 10, 3);
	add_filter('acf/location/rule_match/tax_category_sub', 'tw_acf_location_rules_match_category', 10, 3);
	add_filter('acf/location/rule_match/tax_category_all', 'tw_acf_location_rules_match_category', 10, 3);

	function tw_acf_location_rules_match_category($match, $rule, $options) {

		if (!empty($_REQUEST['tag_ID'])) {

			$current_term_id = intval($_REQUEST['tag_ID']);

			$term_id = intval($rule['value']);

			$match = ($current_term_id == $term_id);

			if ($current_term_id == $term_id) {
				if ($rule['param'] == 'tax_category_sub') {
					$match = false;
				} else {
					$match = true;
				}
			}

			if (!$match and ($rule['param'] == 'tax_category_sub' or $rule['param'] == 'tax_category_all') and $term_children = get_term_children($term_id, 'category')) {
				$match = in_array($current_term_id, $term_children);
			}

			if ($rule['operator'] == "!=") {
				$match = !$match;
			}

		}

		return $match;

	}

}


/**
 * Turn on the local JSON feature to save the field groups in separate files
 */

if (tw_get_setting('acf', 'json_enable')) {

	add_filter('acf/settings/save_json', 'tw_json_save_point');

	function tw_json_save_point() {

		$path = get_stylesheet_directory() . '/includes/acf';

		if (!is_dir($path)) mkdir($path, 0755);

		return $path;

	}

	add_filter('acf/settings/load_json', 'tw_json_load_point');

	function tw_json_load_point($paths) {

		unset($paths[0]);

		$path = get_stylesheet_directory() . '/includes/acf';

		if (!is_dir($path)) mkdir($path, 0755);

		$paths[] = $path;

		return $paths;

	}

}


/**
 * Declare the fallback function if the Advanced Custom Fields plugin is disabled
 */

if (!function_exists('get_field') and !is_admin()) {

	function get_field($field, $post_id = false) {

		if (!$post_id) $post_id = intval(get_the_ID());

		return get_post_meta($post_id, $field, false);

	}

	if (tw_get_setting('acf', 'require_acf')) {

		add_action('wp_footer', 'tw_acf_fallback', 100);

		function tw_acf_fallback() {
			echo '<p style="font-size: 15px; font-family: sans-serif; line-height: 130%; text-align: center; padding: 10px 15px; background: #FDF4F4; border-top: 1px solid #CD9393; color: #8C3535;">Для полноценной работы шаблона необходимо включить плагин Advanced Custom Fields в настройках</p>';
		}

	}

}


/**
 * Add an API key for the Google Maps field
 */

add_filter('acf/settings/google_api_key', function() {
	return 'AIzaSyAJ5QTsj4apSnVK-6T7HMQfUW5-RljJTQ4';
});