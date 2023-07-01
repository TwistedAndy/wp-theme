<?php
/**
 * Optimize how ACF stores flexible content fields, repeaters and field groups
 *
 * The main idea of this optimization is to dramatically
 * reduce the amount of data stored in meta tables by
 * switching to serialized arrays instead of storing every
 * item in a separate field
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/*
 * Read an ACF field value from a serialized array stored in one meta field
 */
add_filter('acf/pre_load_value', 'tw_acf_load_value', 10, 3);

function tw_acf_load_value($values, $post_id, $field) {

	/**
	 * We apply all these changes only to field groups, repeaters,
	 * and flexible content fields because they always have sub fields.
	 *
	 * We use them to map and process the data stored in the database.
	 */

	if (in_array($field['type'], ['group', 'repeater', 'flexible_content', 'clone'])) {

		$entity = acf_decode_post_id($post_id);

		if (!empty($entity['id']) and !empty($entity['type']) and in_array($entity['type'], ['post', 'term', 'comment', 'user', 'option'])) {

			$name = $field['name'];

			/**
			 * ACF saves seamless clone values using the prefix as a key
			 *
			 * In some cases the field contains an original name instead of a prefix
			 */
			if (!empty($field['_clone']) and $clone = acf_get_field($field['_clone'])) {
				$name = $clone['name'];
			}

			if ($entity['type'] == 'option') {
				$data = get_option($post_id . '_' . $name, false);
			} else {
				$data = get_metadata($entity['type'], $entity['id'], $name, true);
			}

			if (is_array($data) and !empty($data)) {

				/**
				 * Process duplicated clone fields correctly
				 */
				if (!empty($field['_clone']) and !empty($field['_name']) and isset($data[$field['_name']])) {
					$data = $data[$field['_name']];
				}

				$data = tw_acf_decode_data($data, $field);

				if (!empty($data)) {
					$values = $data;
				}

			}

		}

	} elseif (!empty($field['_clone'])) {

		/**
		 * Process cloned fields, stored in the array
		 */
		$cloned_field = acf_get_field($field['_clone']);

		$cloned_values = tw_acf_load_value($values, $post_id, $cloned_field);

		if (isset($cloned_values[$field['key']])) {
			return $cloned_values[$field['key']];
		}

	}

	return $values;

}


/*
 * Save an ACF field value to a serialized array stored in one meta field
 */
add_filter('acf/pre_update_value', 'tw_acf_save_value', 10, 4);

function tw_acf_save_value($check, $values, $post_id, $field) {

	if (!empty($field['type']) and in_array($field['type'], ['group', 'repeater', 'flexible_content', 'clone'])) {

		$entity = acf_decode_post_id($post_id);

		if (!empty($entity['id']) and !empty($entity['type']) and in_array($entity['type'], ['post', 'term', 'comment', 'user', 'option'])) {

			$value = tw_acf_encode_data($values, $field);

			/**
			 * It is worth mentioning, that we save the data in one field,
			 * and the field name in other one for compatibility reasons.
			 *
			 * We don't need this optimization on the back end, but it is
			 * substantial on the front end. ACF plugin should be able to
			 * get the correct field key to process the result correctly
			 */
			if ($entity['type'] == 'option') {
				update_option($post_id . '_' . $field['name'], $value, true);
				update_option('_' . $post_id . '_' . $field['name'], $field['key'], true);
			} else {
				update_metadata($entity['type'], $entity['id'], $field['name'], $value);
				update_metadata($entity['type'], $entity['id'], '_' . $field['name'], $field['key']);
			}

			$check = true;

		}

	}

	return $check;

}


/*
 * Decode previously saved ACF flexible content fields, repeaters and field groups
 */
function tw_acf_decode_data($values, $field) {

	if (!is_array($values) or !is_array($field)) {
		return $values;
	}

	$data = [];

	if (!empty($field['layouts']) and is_array($field['layouts'])) {

		/**
		 * Flexible content fields store subfields in the layouts array.
		 * We need to process it first before we can move forward and
		 * process it as a regular field groups
		 */

		$index = 0;

		$layouts = [];

		foreach ($field['layouts'] as $layout) {
			$layouts[$layout['name']] = $layout;
		}

		foreach ($values as $value) {

			if (!empty($value['acf_fc_layout']) and !empty($layouts[$value['acf_fc_layout']])) {
				$data[$index] = tw_acf_decode_data($value, $layouts[$value['acf_fc_layout']]);
				$data[$index]['acf_fc_layout'] = $value['acf_fc_layout'];
			}

			$index++;

		}

		$values = $data;

	} elseif (!empty($field['sub_fields']) and is_array($field['sub_fields'])) {

		/**
		 * The main difference between repeaters and field groups or layouts
		 * is the keys. Repeaters are using numeric keys
		 */

		$key = array_key_first($values);

		if (is_numeric($key)) {

			foreach ($values as $i => $metadata) {

				foreach ($field['sub_fields'] as $sub_field) {

					$name = $sub_field['name'];

					if (isset($metadata[$name])) {
						$value = tw_acf_decode_data($metadata[$name], $sub_field);
					} else {
						$value = '';
					}

					if (in_array($sub_field['type'], ['google_map']) and is_string($value)) {
						$value = json_decode($value, true);
					}

					$data[$i][$sub_field['key']] = $value;

				}

			}

		} else {

			foreach ($field['sub_fields'] as $sub_field) {

				$name = $sub_field['name'];

				if (isset($values[$name])) {
					$value = tw_acf_decode_data($values[$name], $sub_field);
				} else {
					$value = '';
				}

				if (in_array($sub_field['type'], ['google_map']) and is_string($value)) {
					$value = json_decode($value, true);
				}

				$data[$sub_field['key']] = $value;

			}

		}

		$values = $data;

	}

	return $values;

}


/*
 * Encode ACF repeaters and field groups to an array
 */
function tw_acf_encode_data($values, $field) {

	if (!is_array($values) or !is_array($field)) {

		if (is_string($values)) {
			$values = stripslashes($values);
		}

		return $values;

	}

	$data = [];

	if (!empty($field['layouts']) and is_array($field['layouts'])) {

		$layouts = [];

		foreach ($field['layouts'] as $layout) {
			$layouts[$layout['name']] = $layout;
		}

		$index = 0;

		foreach ($values as $value) {

			if (!empty($value['acf_fc_layout']) and !empty($layouts[$value['acf_fc_layout']])) {

				$processed = tw_acf_encode_data($value, $layouts[$value['acf_fc_layout']]);

				if ($processed or is_array($processed)) {
					$data[$index] = $processed;
					$data[$index]['acf_fc_layout'] = $value['acf_fc_layout'];
				}

			}

			$index++;

		}

		$values = $data;

	} elseif (!empty($field['sub_fields']) and is_array($field['sub_fields'])) {

		$fields = [];

		foreach ($field['sub_fields'] as $sub_field) {
			$fields[$sub_field['key']] = $sub_field;
		}

		$key = array_key_first($values);

		/**
		 * Repeaters arrays are using the numeric keys or keys like row-0.
		 * It is very important difference comparing to how we decode data
		 */
		if (!empty($values['acf_fc_layout']) or strpos($key, 'field_') === 0) {

			foreach ($values as $field_key => $value) {

				if (!isset($fields[$field_key]) or empty($fields[$field_key]['name'])) {
					continue;
				}

				$sub_field = $fields[$field_key];

				$processed = tw_acf_encode_data($value, $sub_field);

				if ($processed or is_numeric($processed)) {
					$data[$sub_field['name']] = $processed;
				}

			}

		} else {

			$index = 0;

			foreach ($values as $row) {

				foreach ($row as $field_key => $value) {

					$sub_field = $fields[$field_key];

					if (!empty($sub_field['name'])) {

						$processed = tw_acf_encode_data($value, $sub_field);

						if ($processed or is_numeric($processed)) {
							$data[$index][$sub_field['name']] = $processed;
						}

					}

				}

				$index++;

			}

		}

		$values = $data;

	}

	return $values;

}


/**
 * Increase content width on a term edit screen
 * and scripts for product variations
 */
add_action('admin_head', function() {

	global $post_type;

	if ($post_type == 'product' and function_exists('acf_get_field_groups')) { ?>
		<script type="text/javascript">
			jQuery(function($) {
				$('#woocommerce-product-data').on('woocommerce_variations_loaded', function() {
					acf.doAction('ready');
				});
			});
		</script>
		<?php wp_enqueue_script('jquery-core'); ?>
		<?php wp_enqueue_script('jquery-ui-core'); ?>
	<?php } ?>

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
add_action('init', function() {

	if (function_exists('acf_add_options_sub_page')) {

		acf_add_options_sub_page([
			'page_title' => __('Theme Settings', 'twee'),
			'menu_title' => __('Theme', 'twee'),
			'menu_slug' => 'theme-settings',
			'parent_slug' => 'options-general.php',
			'capability' => 'manage_options',
			'redirect' => false,
			'position' => 90,
			'icon_url' => 'dashicons-star-filled',
			'update_button' => __('Refresh', 'twee'),
			'autoload' => true
		]);

	}

});


/**
 * Save the ACF field groups to JSON files
 */
add_filter('acf/settings/save_json', function() {

	$path = TW_INC . 'acf';

	if (!is_dir($path)) {
		mkdir($path, 0755);
	}

	return $path;

});


/**
 * Load the ACF field groups from JSON files
 */
add_filter('acf/settings/load_json', function($paths) {

	unset($paths[0]);

	$path = TW_INC . 'acf';

	if (!is_dir($path)) {
		mkdir($path, 0755);
	}

	$paths[] = $path;

	return $paths;

});


/**
 * Add an API key for the Google Maps field
 */
add_filter('acf/settings/google_api_key', function() {
	return 'AIzaSyAJ5QTsj4apSnVK-6T7HMQfUW5-RljJTQ4';
});


/**
 * Disable ACF updates
 */
if (function_exists('acf_updates')) {
	remove_filter('pre_set_site_transient_update_plugins', [acf_updates(), 'modify_plugins_transient'], 10, 1);
}


/**
 * Polyfill for the array_key_first function
 */
if (!function_exists('array_key_first')) {
	function array_key_first(array $array) {
		return key(array_slice($array, 0, 1, true));
	}
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

			$value = get_option('options_' . $field);

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


/**
 * Add a new rule for product variations
 */
add_filter('acf/location/rule_values/post_type', function($choices) {
	$choices['product_variation'] = 'Product Variation';
	return $choices;
});


/**
 * Save custom fields for a variation
 */
add_action('woocommerce_save_product_variation', function($variation_id, $i = -1) {

	if (!function_exists('update_field') or empty($_POST['acf']) or !is_array($_POST['acf']) or !isset($_POST['acf'][$i])) {
		return;
	}

	$fields = $_POST['acf'][$i];

	foreach ($fields as $key => $value) {
		update_field($key, $value, $variation_id);
	}

}, 10, 2);


/**
 * Render fields on the variation section
 */
add_action('woocommerce_product_after_variable_attributes', function($loop, $variation_data, $variation) {

	if (!function_exists('acf_get_field_groups')) {
		return;
	}

	tw_app_set('tw_acf_index', $loop);

	add_filter('acf/prepare_field', 'tw_acf_variation_field_name');

	$acf_field_groups = acf_get_field_groups();

	foreach ($acf_field_groups as $acf_field_group) {
		foreach ($acf_field_group['location'] as $group_locations) {
			foreach ($group_locations as $rule) {
				if ($rule['param'] == 'post_type' and $rule['operator'] == '==' and $rule['value'] == 'product_variation') {
					acf_render_fields($variation->ID, acf_get_fields($acf_field_group));
					break 2;
				}
			}
		}
	}

	remove_filter('acf/prepare_field', 'tw_acf_variation_field_name');

}, 10, 3);


/**
 * Adjust the field name
 *
 * @param array $field
 *
 * @return array
 */
function tw_acf_variation_field_name($field) {
	$field['name'] = str_replace('acf[field_', 'acf[' . tw_app_get('tw_acf_index') . '][field_', $field['name']);
	return $field;
}