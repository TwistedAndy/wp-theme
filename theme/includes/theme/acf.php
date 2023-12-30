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
add_filter('acf/pre_load_value', 'tw_acf_load_value', 5, 3);

function tw_acf_load_value($result, $post_id, $field) {

	if ($result !== null) {
		return $result;
	}

	$entity = tw_acf_decode_post_id($post_id);

	if (empty($entity['id']) or empty($entity['type'])) {
		return $result;
	}

	if (!empty($field['_clone'])) {
		$clone = acf_get_field($field['_clone']);
	} else {
		$clone = false;
	}

	if ($clone) {
		$name = $clone['name'];
	} else {
		$name = $field['name'];
	}

	if ($entity['type'] == 'option') {
		$value = get_option($post_id . '_' . $name, false);
	} else {
		$value = get_metadata($entity['type'], $entity['id'], $name, true);
	}

	if (in_array($field['type'], ['group', 'repeater', 'flexible_content', 'clone'])) {

		if (!empty($field['_clone']) and !empty($field['_name']) and is_array($value) and isset($value[$field['_name']])) {
			$value = $value[$field['_name']];
		}

		$value = tw_acf_decode_data($value, $field);

		if (is_array($value)) {
			$result = $value;
		}

	} elseif ($clone) {

		$cloned_values = tw_acf_load_value($result, $post_id, $clone);

		if (isset($cloned_values[$field['key']])) {
			$result = $cloned_values[$field['key']];
		} else {
			$result = $value;
		}

	} else {

		$result = $value;

	}

	return $result;

}


/*
 * Save an ACF field value to a serialized array stored in one meta field
 */
add_filter('acf/pre_update_value', 'tw_acf_save_value', 10, 4);

function tw_acf_save_value($check, $values, $post_id, $field) {

	if ($check !== null or !is_array($field) or empty($field['type'])) {
		return $check;
	}

	$entity = tw_acf_decode_post_id($post_id);

	if (empty($entity['id']) or empty($entity['type'])) {
		return $check;
	}

	$value = tw_acf_encode_data($values, $field);

	$map_key = '_acf_map';

	if ($entity['type'] == 'option') {

		$map_key = $entity['id'] . $map_key;

		$map = get_option($map_key, null);

		if (!is_array($map)) {
			$map = [];
		}

		if (empty($value) and !is_numeric($value)) {

			if (isset($map[$field['name']])) {
				unset($map[$field['name']]);
			}

			delete_option($entity['id'] . '_' . $field['name']);

		} else {

			$map[$field['name']] = str_replace('field_', '', $field['key']);

			update_option($entity['id'] . '_' . $field['name'], $value, true);

		}

		if ($map) {
			update_option($map_key, $map, true);
		} else {
			delete_option($map_key);
		}

	} else {

		$map = get_metadata($entity['type'], $entity['id'], $map_key, true);

		if (!is_array($map)) {
			$map = [];
		}

		if (empty($value) and !is_numeric($value)) {

			if (isset($map[$field['name']])) {
				unset($map[$field['name']]);
			}

			delete_metadata($entity['type'], $entity['id'], $field['name']);

		} else {

			$map[$field['name']] = str_replace('field_', '', $field['key']);

			update_metadata($entity['type'], $entity['id'], $field['name'], $value);

		}

		if ($map) {
			update_metadata($entity['type'], $entity['id'], $map_key, $map);
		} else {
			delete_metadata($entity['type'], $entity['id'], $map_key);
		}

	}

	acf_flush_value_cache($post_id, $field['name']);

	return true;

}


/**
 * Process key loading
 */
add_filter('acf/pre_load_reference', 'tw_acf_load_reference', 10, 3);

function tw_acf_load_reference($result, $field, $post_id) {

	$entity = tw_acf_decode_post_id($post_id);

	if (empty($entity['id']) or empty($entity['type'])) {
		return $result;
	}

	$cache_key = 'acf_map_cache_' . $entity['id'];
	$cache_group = 'twee_meta_' . $entity['type'];

	$value = tw_app_get($cache_key, $cache_group);

	if ($value !== null) {
		return $value;
	}

	$map_key = '_acf_map';

	if ($entity['type'] == 'option') {
		$map = get_option($entity['id'] . $map_key, null);
	} else {
		$map = get_metadata($entity['type'], $entity['id'], $map_key, true);
	}

	if (is_array($map) and !empty($map[$field])) {
		$result = $map[$field];
	}

	if ($result and strpos($result, 'field_') !== 0) {
		$result = 'field_' . $result;
	}

	tw_app_set($cache_key, $result, $cache_group);

	return $result;

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
		 * Repeaters always use numeric keys, but groups usually don't
		 */
		if (!empty($field['type']) and $field['type'] === 'group') {
			$is_repeater = false;
		} else {
			$is_repeater = is_numeric(array_key_first($values));
		}

		if ($is_repeater) {

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
 * Decode the ACF post id
 *
 * @param object|string|int $post_id
 *
 * @return array
 */
function tw_acf_decode_post_id($post_id) {

	$entity = [
		'type' => '',
		'id' => 0
	];

	if (is_numeric($post_id)) {

		$entity = [
			'type' => 'post',
			'id' => (int) $post_id
		];

	} elseif (is_string($post_id)) {

		if (in_array($post_id, ['option', 'options'])) {
			$entity = [
				'type' => 'option',
				'id' => 'option'
			];
		} else {

			$position = strpos($post_id, '_');

			if ($position > 0) {

				$type = substr($post_id, 0, $position);
				$id = substr($post_id, $position + 1);

				if (in_array($type, ['post', 'attachment', 'menu_item'])) {
					$entity = [
						'type' => 'post',
						'id' => (int) $id
					];
				} elseif (in_array($type, ['term', 'menu']) or (taxonomy_exists($type) and is_numeric($id))) {
					$entity = [
						'type' => 'term',
						'id' => (int) $id
					];
				} elseif ($type === 'user') {
					$entity = [
						'type' => 'user',
						'id' => (int) $id
					];
				} elseif ($type === 'widget') {
					$entity = [
						'type' => 'option',
						'id' => 'widget'
					];
				} elseif (in_array($type, ['blog', 'site'])) {
					$entity = [
						'type' => 'blog',
						'id' => (int) $id
					];
				} else {
					$entity = [
						'type' => 'option',
						'id' => $post_id
					];
				}

			}

		}

	} elseif ($post_id instanceof WP_Post) {
		$entity = [
			'type' => 'post',
			'id' => $post_id->ID
		];
	} elseif ($post_id instanceof WP_Term) {
		$entity = [
			'type' => 'term',
			'id' => $post_id->term_id
		];
	} elseif ($post_id instanceof WP_User) {
		$entity = [
			'type' => 'user',
			'id' => $post_id->ID
		];
	} elseif ($post_id instanceof WP_Comment) {
		$entity = [
			'type' => 'comment',
			'id' => (int) $post_id->comment_ID
		];
	}

	return $entity;

}


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
 * Add new options page for the theme settings
 */
add_action('acf/init', function() {
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
});


/**
 * Specify the API key for the Google Maps field
 */
add_filter('acf/settings/google_api_key', function() {
	return get_option('options_google_api_key', 'AIzaSyAJ5QTsj4apSnVK-6T7HMQfUW5-RljJTQ4');
});


/**
 * Include a few style adjustments
 */
add_action('admin_head', function() {

	global $post_type;

	if ($post_type == 'product' and function_exists('acf_get_field_groups')) { ?>
		<script type="text/javascript">
			jQuery(function($) {
				$('#woocommerce-product-data').on('woocommerce_variations_loaded', function() {
					acf.doAction('ready');
				});
				$('.acf-fields').on('mousewheel', 'input[type="number"]', function(e) {
					$(this).blur();
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

		.acf-repeater .acf-row:hover > .acf-row-handle .acf-icon.show-on-shift,
		.acf-repeater .acf-row.-hover > .acf-row-handle .acf-icon.show-on-shift {
			top: auto;
			z-index: 1;
			bottom: -12px;
			display: block !important;
		}

		#your-profile .acf-field textarea, #createuser .acf-field textarea {
			max-width: none;
			width: 100%;
		}
	</style>

<?php });


/**
 * Add support for WooCommerce product variations
 */
if (class_exists('WooCommerce')) {

	/**
	 * Add a new rule for product variations
	 */
	add_filter('acf/location/rule_values/post_type', function($choices) {
		$choices['product_variation'] = __('Product Variation', 'twee');
		return $choices;
	});


	/**
	 * Save custom fields for a variation
	 */
	add_action('woocommerce_save_product_variation', function($variation_id, $i = -1) {

		if (!function_exists('update_field') or empty($_POST['acf_variations']) or !is_array($_POST['acf_variations']) or !isset($_POST['acf_variations'][$i])) {
			return;
		}

		$fields = $_POST['acf_variations'][$i];

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
		$field['name'] = str_replace('acf[field_', 'acf_variations[' . tw_app_get('tw_acf_index') . '][field_', $field['name']);
		return $field;
	}

}


/**
 * A fallback for the get field function
 */
if (!function_exists('get_field')) {

	function get_field($field, $post_id = false, $format = true) {

		$entity = tw_acf_decode_post_id($post_id);

		if (empty($entity['id']) or empty($entity['type'])) {
			return null;
		}

		if ($entity['type'] === 'option') {
			$value = get_option($entity['id'] . '_' . $field, null);
		} else {
			$value = get_metadata($entity['type'], $entity['id'], $field, true);
		}

		return $value;

	}

}