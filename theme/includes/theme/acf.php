<?php
/**
 * New ACF data storage engine
 *
 * The code below implements the following optimizations:
 * - Groups, Repeaters, and Flexible Content fields are stored as serialized arrays
 * - Field association is stored in one field per object
 * - Existing data will be converted on save
 * - Revisions are supported
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/*
 * Load a compressed field value and return it in the ACF format
 */
add_filter('acf/pre_load_value', 'tw_acf_load_value', 20, 3);

function tw_acf_load_value($result, $post_id, $field) {

	if ($result !== null or (is_string($post_id) and strpos($post_id, 'field_') === 0)) {
		return $result;
	}

	$entity = tw_acf_decode_post_id($post_id);

	if (empty($entity['id']) or empty($entity['type'])) {
		return null;
	}

	if ($field['type'] === 'clone' and !empty($field['sub_fields'])) {

		$values = [];

		foreach ($field['sub_fields'] as $sub_field) {
			$values[$sub_field['key']] = tw_acf_load_value(null, $post_id, $sub_field);
		}

		return $values;

	}

	if ($entity['type'] == 'option') {
		$result = get_option($post_id . '_' . $field['name'], false);
	} else {
		$result = get_metadata($entity['type'], $entity['id'], $field['name'], true);
	}

	/**
	 * Allow ACF to load flexible content fields in old format
	 */
	if (!empty($field['layouts']) and is_array($result) and is_string(reset($result))) {
		return null;
	}

	if (in_array($field['type'], ['group', 'repeater', 'flexible_content'])) {

		$result = tw_acf_decode_data($result, $field);

		if (!is_array($result)) {
			return null;
		}

		if (!empty($field['pagination']) and (acf_get_data('acf_is_rendering') or doing_action('wp_ajax_acf/ajax/query_repeater'))) {

			if (acf_get_data('acf_inside_rest_call') or doing_action('wp_ajax_acf/ajax/fetch-block')) {
				return $result;
			}

			$per_page = isset($field['rows_per_page']) ? (int) $field['rows_per_page'] : 20;

			$chunks = array_chunk($result, $per_page);

			if (acf_get_data('acf_is_rendering') or empty($_POST['paged']) or $_POST['paged'] < 1) {
				$index = 0;
			} else {
				$index = (int) $_POST['paged'] - 1;
			}

			if (!empty($chunks[$index])) {
				return $chunks[$index];
			} else {
				return [];
			}

		}

	} elseif ($field['type'] == 'google_map' and is_string($result)) {

		$result = json_decode($result, true);

	}

	return $result;

}


/*
 * Convert a field value in the ACF format and save it
 */
add_filter('acf/pre_update_value', 'tw_acf_save_value', 20, 4);

function tw_acf_save_value($check, $values, $post_id, $field) {

	if ($check !== null or !is_array($field) or empty($field['type']) or (is_string($post_id) and strpos($post_id, 'field_') === 0)) {
		return $check;
	}

	$entity = tw_acf_decode_post_id($post_id);

	if (empty($entity['id']) or empty($entity['type'])) {
		return null;
	}

	if ($field['type'] === 'clone' and !empty($field['sub_fields']) and is_array($values)) {

		foreach ($field['sub_fields'] as $sub_field) {
			if (isset($values[$sub_field['key']])) {
				tw_acf_save_value(null, $values[$sub_field['key']], $post_id, $sub_field);
			}
		}

		return true;

	}

	$value = tw_acf_encode_data($values, $field);

	$map_key = '_acf_map';

	if ($field['type'] == 'repeater' and !empty($field['pagination']) and did_action('acf/save_post') and !isset($_POST['_acf_form'])) {

		if ($entity['type'] == 'option') {
			$old_values = get_option($entity['id'] . $field['name'], null);
		} else {
			$old_values = get_metadata($entity['type'], $entity['id'], $field['name'], true);
		}

		if (!is_array($old_values)) {
			$old_values = [];
		}

		$per_page = isset($field['rows_per_page']) ? (int) $field['rows_per_page'] : 20;

		$index = isset($_POST['paged']) ? (int) $_POST['paged'] : 0;

		if ($index > 0) {
			$index = $index - 1;
		}

		$chunks = array_chunk($old_values, $per_page);

		if (isset($chunks[$index])) {
			$chunks[$index] = $value;
		} else {
			$chunks[] = $value;
		}

		$value = [];

		foreach ($chunks as $chunk) {
			$value = array_merge($value, $chunk);
		}

	}

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
 * Load a field key from the map field
 */
add_filter('acf/pre_load_reference', 'tw_acf_load_reference', 10, 3);

function tw_acf_load_reference($result, $field, $post_id) {

	$entity = tw_acf_decode_post_id($post_id);

	if (empty($entity['id']) or empty($entity['type'])) {
		return $result;
	}

	$cache_key = 'acf_map_cache';
	$cache_group = 'twee_meta_' . $entity['type'];

	if ($entity['type'] != 'option' and $entity['id'] > 0) {
		$cache_group .= '_' . $entity['id'];
	}

	$map = wp_cache_get($cache_key, $cache_group);

	if (!is_array($map)) {

		$map_key = '_acf_map';

		if ($entity['type'] == 'option') {
			$map = get_option($entity['id'] . $map_key, null);
		} else {
			$map = get_metadata($entity['type'], $entity['id'], $map_key, true);
		}

		if (!is_array($map)) {
			$map = [];
		}

		wp_cache_set($cache_key, $map, $cache_group);

	}

	if (!empty($map[$field]) and strpos($map[$field], 'field_') !== 0) {
		$result = 'field_' . $map[$field];
	}

	return $result;

}


/**
 * Adjust the total number of rows for repeaters
 */
add_action('acf/pre_render_field', function($field) {

	if ($field['type'] == 'repeater') {
		add_filter('acf/pre_load_metadata', 'tw_acf_total_rows', 10, 3);
		acf_set_data('acf_is_rendering', true);
	}

	return $field;

}, 5);


/**
 * Get the total number of rows for repeater fields
 *
 * @param int|null          $value
 * @param object|string|int $post_id
 * @param string            $name
 *
 * @return int|null
 */
function tw_acf_total_rows($value, $post_id, $name) {

	$entity = tw_acf_decode_post_id($post_id);

	if (empty($entity['id']) or empty($entity['type'])) {
		return $value;
	}

	if ($entity['type'] == 'option') {
		$data = get_option($entity['id'] . $name, null);
	} else {
		$data = get_metadata($entity['type'], $entity['id'], $name, true);
	}

	if (is_array($data)) {
		$value = count($data);
	} elseif (is_numeric($data)) {
		$value = (int) $data;
	}

	acf_set_data('acf_is_rendering', false);
	remove_filter('acf/pre_load_metadata', 'tw_acf_total_rows', 10);

	return $value;

}


/*
 * Convert a field value in the compact format
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

				if (!empty($row['acf_deleted'])) {
					continue;
				}

				foreach ($row as $field_key => $value) {

					if (!isset($fields[$field_key])) {
						continue;
					}

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


/*
 * Convert a field value in the ACF format
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

				$data[$sub_field['key']] = $value;

			}

		}

		$values = $data;

	}

	return $values;

}


/**
 * Decode the ACF Post ID
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
 * Compress all ACF fields in the compact format
 *
 * @param string $meta_type
 * @param int    $object_id
 *
 * @return void
 */
function tw_acf_compress_meta($meta_type = 'post', $object_id = 0) {

	if (!empty($_GET['action']) and $_GET['action'] == 'restore') {
		return;
	}

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

		if (empty($meta_value) or strpos($meta_key, '_') !== 0 or strpos($meta_value, 'field_') !== 0) {
			continue;
		}

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

	if (empty($acf_values)) {
		return;
	}

	ksort($acf_values);

	$acf_values = tw_acf_compress_walker($acf_values, $acf_fields);

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
function tw_acf_compress_walker($values, $fields) {

	if (!is_array($values) or empty($values)) {
		return $values;
	}

	foreach ($values as $key => $value) {

		if ($value === '' and isset($fields[$key]) and tw_acf_compress_match($values, $key . '_')) {

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

				$values[$key] = tw_acf_compress_walker($sub_values, $sub_fields);

			}

		} elseif (tw_acf_compress_match($values, $key . '_0_')) {

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

					$values[$key][$index] = tw_acf_compress_walker($sub_values, $sub_fields);

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
function tw_acf_compress_match($array, $needle) {

	foreach ($array as $key => $value) {
		if (strpos($key, $needle) === 0) {
			return true;
		}
	}

	return false;

}


/**
 * Decompress ACF metadata to the default format
 *
 * @param string $meta_type
 * @param int    $object_id
 *
 * @return void
 */
function tw_acf_decompress_meta($meta_type = 'post', $object_id = 0) {

	remove_filter('acf/pre_update_value', 'tw_acf_save_value', 20);
	remove_filter('acf/pre_load_value', 'tw_acf_load_value', 20);
	remove_filter('acf/pre_load_reference', 'tw_acf_load_reference', 10);

	$fields = tw_acf_decompress_fields($meta_type, $object_id, false);

	if (empty($fields)) {
		return;
	}

	foreach ($fields as $key => $field) {
		update_metadata($meta_type, $object_id, '_' . $key, $field['key']);
		update_metadata($meta_type, $object_id, $key, $field['value']);
	}

	delete_metadata($meta_type, $object_id, '_acf_map');

}


/**
 * Decompress ACF metadata in a fields array
 *
 * @param string $meta_type
 * @param int    $object_id
 * @param bool   $include_layouts
 *
 * @return array
 */
function tw_acf_decompress_fields($meta_type = 'post', $object_id = 0, $include_layouts = false) {

	if (!function_exists('acf_get_field')) {
		return [];
	}

	$cache_key = 'unconvert_data';
	$cache_group = 'twee_meta_' . $meta_type;

	if ($meta_type != 'option' and $object_id > 0) {
		$cache_group .= '_' . $object_id;
	}

	if ($include_layouts) {
		$cache_key .= '_layouts';
	}

	$data = wp_cache_get($cache_key, $cache_group);

	if (is_array($data)) {
		return $data;
	}

	$data = [];

	$object_id = (int) $object_id;

	$map = get_metadata($meta_type, $object_id, '_acf_map', true);

	if (!is_array($map)) {
		return $data;
	}

	$result = [];

	foreach ($map as $key => $field) {

		$field = acf_get_field('field_' . $field);

		if (empty($field) or !is_array($field)) {
			continue;
		}

		$data = get_metadata($meta_type, $object_id, $key, true);

		if ($data) {
			$result = tw_acf_decompress_walker($result, $data, $key, $field, $include_layouts);
		}

	}

	ksort($result);

	wp_cache_set($cache_key, $result, $cache_group);

	return $result;

}


/**
 * Recursively transform the nested array with
 * field data into a simple array with fields
 *
 * @param array  $result
 * @param mixed  $data
 * @param string $base_key
 * @param array  $field
 * @param bool   $include_layouts
 *
 * @return array
 */
function tw_acf_decompress_walker($result, $data, $base_key, $field, $include_layouts = false) {

	if (!is_array($field) or empty($field['name'])) {
		return $result;
	}

	$field_key = $base_key . '_' . $field['name'];
	$field_value = $data;

	if ($field['type'] == 'flexible_content' and !empty($field['layouts']) and is_array($data)) {

		$field_value = [];
		$field_key = $base_key;

		foreach ($data as $index => $value) {

			if (!is_array($value) or empty($value['acf_fc_layout'])) {
				continue;
			}

			$layout = false;

			foreach ($field['layouts'] as $item) {
				if ($item['name'] == $value['acf_fc_layout'] and !empty($item['sub_fields'])) {
					$layout = $item;
					break;
				}
			}

			if (empty($layout)) {
				continue;
			}

			$field_value[] = $layout['name'];

			$key = $base_key . '_' . $index;

			foreach ($value as $field_name => $field_data) {

				$sub_field = false;

				foreach ($layout['sub_fields'] as $item) {
					if ($item['name'] == $field_name) {
						$sub_field = $item;
						break;
					}
				}

				if ($sub_field) {
					$result = tw_acf_decompress_walker($result, $field_data, $key, $sub_field);
				}

			}

			if ($include_layouts) {
				$result[$key] = [
					'key' => $layout['key'],
					'name' => $layout['name'],
					'type' => 'layout',
					'label' => $layout['label'],
					'value' => $layout['name']
				];
			}

		}

	} elseif ($field['type'] === 'group' and !empty($field['sub_fields']) and is_array($data)) {

		foreach ($data as $index => $value) {

			$sub_field = false;

			foreach ($field['sub_fields'] as $item) {
				if ($item['name'] == $index) {
					$sub_field = $item;
					break;
				}
			}

			if (empty($sub_field)) {
				continue;
			}

			$result = tw_acf_decompress_walker($result, $value, $field_key, $sub_field);

		}

		$field_value = '';

	} elseif ($field['type'] === 'repeater' and !empty($field['sub_fields']) and is_array($data)) {

		foreach ($data as $index => $value) {

			if (!is_array($value)) {
				continue;
			}

			foreach ($value as $field_name => $field_data) {

				$sub_field = false;

				foreach ($field['sub_fields'] as $item) {
					if ($item['name'] == $field_name) {
						$sub_field = $item;
						break;
					}
				}

				if ($sub_field) {
					$result = tw_acf_decompress_walker($result, $field_data, $field_key . '_' . $index, $sub_field);
				}

			}

		}

		$field_value = count($data);

	}

	$result[$field_key] = [
		'key' => $field['key'],
		'name' => $field['name'],
		'type' => $field['type'],
		'label' => $field['label'],
		'value' => $field_value
	];

	return $result;

}


/**
 * Include compressed ACF data to the revision fields
 */
add_filter('_wp_post_revision_fields', 'tw_acf_revision_fields', 15, 2);

function tw_acf_revision_fields($result, $post) {

	if (!is_array($post) or empty($post['ID']) or !function_exists('acf_get_field')) {
		return $result;
	}

	$cache_key = 'acf_revision_fields';
	$cache_group = 'twee_meta_post_' . $post['ID'];

	$fields = wp_cache_get($cache_key, $cache_group, null);

	if (is_array($fields)) {

		if ($fields) {
			if (is_array($result)) {
				$result = array_merge($result, $fields);
			} else {
				$result = $fields;
			}
		}

		return $result;

	}

	$fields = [];

	$filters_key = 'acf_revision_filters';

	$filters = wp_cache_get($filters_key, $cache_group);

	if (!is_array($filters)) {
		$filters = [];
	}

	$data = tw_acf_decompress_fields('post', $post['ID'], true);

	$revisions = wp_get_post_revisions($post['ID']);

	foreach ($revisions as $revision) {

		$revision_data = tw_acf_decompress_fields('post', $revision->ID, true);

		foreach ($revision_data as $key => $field) {
			if (!isset($data[$key])) {
				$data[$key] = $field;
			}
		}

	}

	$block = '';

	$prefix = 'twee_acf_';

	foreach ($data as $key => $field) {

		if (!empty($field['type']) and $field['type'] == 'layout') {
			$block = $field['label'] . ' - ';
			continue;
		}

		$fields[$prefix . $key] = $block . $field['label'];

		/**
		 * Do not add more than one filter for a field
		 */
		if (!in_array($key, $filters)) {

			$filters[] = $key;

			add_filter('_wp_post_revision_field_' . $prefix . $key, function($result, $key, $post) {

				$key = substr($key, 9);

				$data = tw_acf_decompress_fields('post', $post->ID, true);

				if ($data and !empty($data[$key]) and isset($data[$key]['value'])) {

					$result = $data[$key]['value'];

					if (is_array($result)) {
						$result = stripslashes(json_encode(array_filter($result), JSON_PRETTY_PRINT));
					}

				} else {

					$result = '';

				}

				return $result;

			}, 20, 3);

		}

	}

	wp_cache_set($cache_key, $fields, $cache_group);
	wp_cache_set($filters_key, $filters, $cache_group);

	if ($fields) {
		if (is_array($result)) {
			$result = array_merge($result, $fields);
		} else {
			$result = $fields;
		}
	}

	return $result;

}


/**
 * Restore the ACF data when a revision is restored
 */
add_action('wp_restore_post_revision', 'tw_acf_revision_restore', 10, 2);

function tw_acf_revision_restore($post_id, $revision_id) {

	$map = get_post_meta($revision_id, '_acf_map', true);

	$revision = get_post($revision_id);

	if (empty($map) or !is_array($map) or !($revision instanceof WP_Post)) {
		return;
	}

	if ($revision->post_type != 'revision' or $revision->post_parent != $post_id) {
		return;
	}

	foreach ($map as $key => $field) {
		$value = get_post_meta($revision_id, $key, true);
		update_metadata('post', $post_id, $key, $value);
	}

	update_metadata('post', $post_id, '_acf_map', $map);

}


/**
 * Copy the compressed ACF data to a new revision
 */
add_action('_wp_put_post_revision', 'tw_acf_revision_create', 20, 2);

function tw_acf_revision_create($revision_id) {

	$revision = get_post($revision_id);

	if (!($revision instanceof WP_Post) or empty($revision->post_parent) or $revision->post_type !== 'revision') {
		return;
	}

	$post_id = $revision->post_parent;

	$map = get_post_meta($post_id, '_acf_map', true);

	if (empty($map) or !is_array($map)) {
		return;
	}

	foreach ($map as $key => $field) {
		$value = get_post_meta($post_id, $key, true);
		update_metadata('post', $revision->ID, $key, $value);
	}

	update_metadata('post', $revision->ID, '_acf_map', $map);

}


/**
 * Compress and clean metadata before ACF processing
 */
add_action('edit_comment', function($object_id) {
	tw_acf_compress_meta('comment', $object_id);
}, 5, 1);

add_action('profile_update', function($object_id) {
	tw_acf_compress_meta('user', $object_id);
}, 5, 1);

add_action('edit_term', function($object_id) {
	tw_acf_compress_meta('term', $object_id);
}, 5, 1);

add_action('save_post', function($object_id) {
	tw_acf_compress_meta('post', $object_id);
}, 5, 1);


/**
 * Refresh cache on the option page update
 */
add_action('acf/save_post', function($post_id) {
	if ($post_id == 'option' or $post_id == 'options') {
		tw_app_clear('twee_meta_option');
		wp_cache_flush_group('twee_meta_option');
	}
}, 10, 2);