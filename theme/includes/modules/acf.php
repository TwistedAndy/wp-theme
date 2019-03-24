<?php
/**
 * Advanced Custom Fields library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Retrieve the current page id for the get_field() function
 *
 * @return string ID of the current page
 */

function tw_acf_get_current_id() {

	$post_id = false;

	if (is_category() or is_tag() or is_tax()) {

		$post_id = get_queried_object();

	} elseif (is_singular()) {

		$post_id = get_the_ID();

	}

	return $post_id;

}


/**
 * Add new options page for the theme settings
 */

if (tw_get_setting('modules', 'acf', 'options_page') and function_exists('acf_add_options_page')) {

	add_action('init', 'tw_action_add_options_page');

	function tw_action_add_options_page() {

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

}


/**
 * Check the subcategories too when displaying the fields for the specified category
 */

if (tw_get_setting('modules', 'acf', 'include_subcats')) {

	add_filter('acf/location/rule_match/post_category', 'tw_filter_match_subcategories', 10, 3);

	function tw_filter_match_subcategories($match, $rule, $options) {

		$data = acf_decode_taxonomy_term($rule['value']);
		
		if (!empty($data['taxonomy']) and !empty($data['term']) and !empty($options['post_id'])) {

			if (is_numeric($data['term'])) {
				$term = get_term_by('id', $data['term'], $data['taxonomy']);
			} else {
				$term = get_term_by('slug', $data['term'], $data['taxonomy']);
			}

			if ($term instanceof WP_Term) {

				$post_id = intval($options['post_id']);

				$term_id = $term->term_id;

				if (!empty($options['ajax']) and !empty($options['post_terms'][$data['taxonomy']])) {
					$terms = $options['post_terms'][$data['taxonomy']];
				} else {
					$terms = tw_post_terms($post_id, $data['taxonomy'], true, true);
				}

				if (is_array($terms) and count($terms) > 0) {

					if ($rule['operator'] == '==') {
						$return = true;
					} else {
						$return = false;
					}

					if (in_array($term_id, $terms)) {

						return $return;

					} else {

						foreach ($terms as $category) {
							if ($parents = get_ancestors($category, $data['taxonomy'])) {
								if (in_array($term_id, $parents)) {
									return $return;
								}
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

if (tw_get_setting('modules', 'acf', 'category_rules')) {

	add_filter('acf/location/rule_types', 'tw_filter_location_rules_types');

	function tw_filter_location_rules_types($choices) {

		$choices[__('Taxonomy', 'wp-theme')] = array(
			'tax_category' => __('Category', 'wp-theme'),
			'tax_category_sub' => __('Subcategories', 'wp-theme'),
			'tax_category_all' => __('Category and subcategories', 'wp-theme'),
		);

		return $choices;

	}

	add_filter('acf/location/rule_values/tax_category', 'tw_filter_location_rules_values_category');
	add_filter('acf/location/rule_values/tax_category_all', 'tw_filter_location_rules_values_category');
	add_filter('acf/location/rule_values/tax_category_sub', 'tw_filter_location_rules_values_category');

	function tw_filter_location_rules_values_category($choices) {

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

	add_filter('acf/location/rule_match/tax_category', 'tw_filter_location_rules_match_category', 10, 3);
	add_filter('acf/location/rule_match/tax_category_sub', 'tw_filter_location_rules_match_category', 10, 3);
	add_filter('acf/location/rule_match/tax_category_all', 'tw_filter_location_rules_match_category', 10, 3);

	function tw_filter_location_rules_match_category($match, $rule, $options) {

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

if (tw_get_setting('modules', 'acf', 'json_enable')) {

	add_filter('acf/settings/save_json', 'tw_filter_json_save');

	function tw_filter_json_save() {

		$path = get_stylesheet_directory() . '/includes/acf';

		if (!is_dir($path)) mkdir($path, 0755);

		return $path;

	}

	add_filter('acf/settings/load_json', 'tw_filter_json_load');

	function tw_filter_json_load($paths) {

		unset($paths[0]);

		$path = get_stylesheet_directory() . '/includes/acf';

		if (!is_dir($path)) mkdir($path, 0755);

		$paths[] = $path;

		return $paths;

	}

}


/**
 * Add an API key for the Google Maps field
 */

if (tw_get_setting('modules', 'acf', 'google_api')) {

	add_filter('acf/settings/google_api_key', 'tw_filter_google_api');

	function tw_filter_google_api() {

		return 'AIzaSyAJ5QTsj4apSnVK-6T7HMQfUW5-RljJTQ4';

	}

}


/**
 * Declare a fallback function for the ACF plugin
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