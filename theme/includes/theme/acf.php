<?php
/**
 * New ACF data storage engine
 *
 * The code below implements the following optimizations:
 * - Groups, Repeaters, and Flexible Content fields are stored as serialized arrays
 * - Field association is stored in one field per object
 * - Existing data will be converted on save
 * - Add ACF support for product variations
 * - Adds a fallback get_field function
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
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

	/**
	 * Allow ACF to load flexible content fields in old format
	 */
	if (!empty($field['layouts']) and is_array($value) and is_string(reset($value))) {
		return $result;
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

			$field_key = $field['key'];

			if (strpos($field_key, 'field_') === 0) {
				$map[$field['name']] = substr($field_key, 6);
			}

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

			$field_key = $field['key'];

			if (strpos($field_key, 'field_') === 0) {
				$map[$field['name']] = substr($field_key, 6);
			}

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

		$post_id = trim(strtolower($post_id));

		$entity = [
			'type' => 'option',
			'id' => $post_id
		];

		$position = strpos($post_id, '_');

		if ($position > 0) {

			$type = substr($post_id, 0, $position);
			$id = (int) substr($post_id, $position + 1);

			if (in_array($type, ['post', 'attachment', 'menu_item'])) {
				$entity = [
					'type' => 'post',
					'id' => $id
				];
			} elseif (in_array($type, ['term', 'menu']) or taxonomy_exists($type)) {
				$entity = [
					'type' => 'term',
					'id' => $id
				];
			} elseif ($type === 'user') {
				$entity = [
					'type' => 'user',
					'id' => $id
				];
			} elseif ($type === 'widget') {
				$entity = [
					'type' => 'option',
					'id' => $post_id
				];
			} elseif (in_array($type, ['blog', 'site'])) {
				$entity = [
					'type' => 'blog',
					'id' => $id
				];
			} elseif ($type == 'block') {
				$entity = [
					'type' => 'block',
					'id' => $post_id
				];
			}

		} elseif ($post_id == 'option') {

			$entity['id'] = 'options';

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
 * Decompress ACF metadata in the normal format
 *
 * @param string $meta_type
 * @param int    $object_id
 *
 * @return void
 */
function tw_acf_decompress_meta($meta_type = 'post', $object_id = 0) {

	remove_filter('acf/pre_update_value', 'tw_acf_save_value', 10);
	remove_filter('acf/pre_load_value', 'tw_acf_load_value', 5);
	remove_filter('acf/pre_load_reference', 'tw_acf_load_reference', 10);

	$map = get_metadata($meta_type, $object_id, '_acf_map', true);

	if (empty($map) or !function_exists('acf_get_field')) {
		return;
	}

	if ($meta_type == 'post') {
		$post_id = $object_id;
	} else {
		$post_id = $meta_type . '_' . $object_id;
	}

	$post_id = acf_get_valid_post_id($post_id);

	foreach ($map as $meta_key => $field_key) {

		$value = get_metadata($meta_type, $object_id, $meta_key, true);
		$field = acf_get_field('field_' . $field_key);

		if ($field) {
			$field['value'] = $value;
			acf_update_value($value, $post_id, $field);
		} else {
			update_field($meta_key, $value, $post_id);
		}

	}

	delete_metadata($meta_type, $object_id, '_acf_map');

}


/**
 * Compress existing ACF metadata in the compact format
 *
 * @param string $meta_type
 * @param int    $object_id
 *
 * @return void
 */
function tw_acf_compress_meta($meta_type = 'post', $object_id = 0) {

	$metadata = get_metadata_raw($meta_type, $object_id, '', false);

	if (empty($metadata) or !is_array($metadata)) {
		return;
	}

	$acf_fields = [];
	$acf_values = [];
	$acf_remove = [];

	$metadata = array_map(function($array) {
		return reset($array);
	}, $metadata);

	foreach ($metadata as $meta_key => $meta_value) {

		if (strpos($meta_key, '_') === 0 and strpos($meta_value, 'field_') === 0) {

			$data_key = substr($meta_key, 1);

			if (!isset($metadata[$data_key])) {
				continue;
			}

			$metadata[$data_key] = maybe_unserialize($metadata[$data_key]);

			$acf_remove[] = $meta_key;
			$acf_remove[] = $data_key;

			$acf_fields[$data_key] = $meta_value;
			$acf_values[$data_key] = $metadata[$data_key];

		}

	}

	if (empty($acf_values)) {
		return;
	}

	ksort($acf_values);

	$acf_values = tw_acf_compress_values($acf_values, $acf_fields);

	$acf_remove = array_diff($acf_remove, array_keys($acf_values));

	if ($acf_remove) {
		foreach ($acf_remove as $meta_key) {
			delete_metadata($meta_type, $object_id, $meta_key);
		}
	}

	if (!empty($metadata['_acf_map']) and is_array($metadata['_acf_map'])) {
		$acf_map = $metadata['_acf_map'];
	} else {
		$acf_map = [];
	}

	foreach ($acf_values as $meta_key => $meta_value) {

		if (!empty($acf_map[$meta_key])) {
			continue;
		}

		if ($meta_value === '') {
			unset($acf_map[$meta_key]);
			delete_metadata($meta_key, $object_id, $meta_key);
			continue;
		}

		if (isset($acf_fields[$meta_key]) and strpos($acf_fields[$meta_key], 'field_') === 0) {
			$acf_map[$meta_key] = substr($acf_fields[$meta_key], 6);
		}

		$current_value = $metadata[$meta_key] ?? '';

		if ($meta_value !== $current_value) {
			update_metadata($meta_type, $object_id, $meta_key, $meta_value);
		}

	}

	if ($acf_map) {
		update_metadata($meta_type, $object_id, '_acf_map', $acf_map);
	} else {
		delete_metadata($meta_type, $object_id, '_acf_map');
	}

}


/**
 * Convert fields with ACF values into array
 *
 * @param array $values
 * @param array $fields
 *
 * @return array
 */
function tw_acf_compress_values($values, $fields) {

	if (!is_array($values) or empty($values)) {
		return $values;
	}

	foreach ($values as $key => $value) {

		if ($value === '' and isset($fields[$key]) and tw_acf_compress_find($values, $key . '_')) {

			/**
			 * Group fields use a base key as a prefix
			 */
			$field_key = $fields[$key];

			/**
			 * Process cloned field groups
			 */
			$position = strpos($field_key, '_field_');

			if ($position > 0) {
				$field_key = substr($field_key, $position + 1);
			}

			if (function_exists('acf_get_field')) {
				$field = acf_get_field($field_key);
			} else {
				$field = false;
			}

			if (is_array($field) and $field['type'] == 'group') {

				$needle = $key . '_';
				$length = strlen($needle);

				$sub_values = [];
				$sub_fields = [];

				foreach ($values as $sub_key => $sub_value) {

					if (strpos($sub_key, $needle) === 0) {

						$trimmed_key = substr($sub_key, $length);
						$sub_values[$trimmed_key] = $sub_value;

						if (isset($fields[$sub_key])) {
							$sub_fields[$trimmed_key] = $fields[$sub_key];
						}

						unset($values[$sub_key]);
						unset($fields[$sub_key]);

					}

				}

				$values[$key] = tw_acf_compress_values($sub_values, $sub_fields);

			}

		} elseif (tw_acf_compress_find($values, $key . '_0_')) {

			/**
			 * Process repeaters and flexible content fields
			 *
			 * Repeater fields use a number of iterations
			 * Flexible content use an array with layouts
			 */
			if (is_array($value) and is_string(reset($value))) {
				$count = count($value);
			} elseif (is_numeric($value) and $value > 0) {
				$count = (int) $value;
			} else {
				$count = 0;
			}

			if ($count > 0) {

				$values[$key] = [];

				for ($index = 0; $index < $count; $index++) {

					$needle = $key . '_' . $index . '_';
					$length = strlen($needle);

					$sub_values = [];
					$sub_fields = [];

					foreach ($values as $sub_key => $sub_value) {

						if (strpos($sub_key, $needle) === 0) {

							$trimmed_key = substr($sub_key, $length);
							$sub_values[$trimmed_key] = $sub_value;

							if (isset($fields[$sub_key])) {
								$sub_fields[$trimmed_key] = $fields[$sub_key];
							}

							unset($values[$sub_key]);
							unset($fields[$sub_key]);

						}

					}

					$values[$key][$index] = tw_acf_compress_values($sub_values, $sub_fields);

					if (is_array($value) and isset($value[$index])) {
						$values[$key][$index]['acf_fc_layout'] = $value[$index];
					}

				}

			}

		}

	}

	return $values;

}


/**
 * Check if an array contains a key starting with a string
 *
 * @param array  $array
 * @param string $needle
 *
 * @return bool
 */
function tw_acf_compress_find($array, $needle) {

	foreach ($array as $key => $value) {
		if (strpos($key, $needle) === 0) {
			return true;
		}
	}

	return false;

}


/**
 * Compress and clean metadata before ACF processing
 */
add_action('edit_comment', function($post_id) {
	tw_acf_compress_meta('comment', $post_id);
}, 5, 1);

add_action('profile_update', function($post_id) {
	tw_acf_compress_meta('user', $post_id);
}, 5, 1);

add_action('edit_term', function($post_id) {
	tw_acf_compress_meta('term', $post_id);
}, 5, 1);

add_action('save_post', function($post_id) {
	tw_acf_compress_meta('post', $post_id);
}, 5, 1);