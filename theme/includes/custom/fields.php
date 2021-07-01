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
 * @version 3.0
 */

/**
 * Polyfill for the array_key_first function
 */
if (!function_exists('array_key_first')) {
	function array_key_first(array $array) {
		return key(array_slice($array, 0, 1, true));
	}
}


/*
 * Read an ACF field value from a serialized array stored in one meta field
 */
add_filter('acf/pre_load_value', 'tw_acf_display_options_field', 10, 3);

function tw_acf_display_options_field($values, $post_id, $field) {

	/**
	 * We apply all these changes only to field groups, repeaters,
	 * and flexible content fields because they always have sub fields.
	 *
	 * We use them to map and process the data stored in the database.
	 */
	if (!empty($field['type']) and in_array($field['type'], ['group', 'repeater', 'flexible_content'])) {

		$entity = acf_decode_post_id($post_id);

		if (!empty($entity['id']) and !empty($entity['type']) and in_array($entity['type'], ['post', 'term', 'comment', 'user'])) {

			$data = get_metadata($entity['type'], $entity['id'], $field['name'], true);

			if (is_array($data) and !empty($data)) {

				$data = tw_acf_decode_data($data, $field);

				if (!empty($data)) {
					$values = $data;
				}

			}

		}

	}

	return $values;

}


/*
 * Save an ACF field value to a serialized array stored in one meta field
 */
add_filter('acf/pre_update_value', 'tw_acf_update_options_field', 10, 4);

function tw_acf_update_options_field($check, $values, $post_id, $field) {

	if (!empty($field['type']) and in_array($field['type'], ['group', 'repeater', 'flexible_content'])) {

		$entity = acf_decode_post_id($post_id);

		if (!empty($entity['id']) and !empty($entity['type']) and in_array($entity['type'], ['post', 'term', 'comment', 'user'])) {

			$value = tw_acf_encode_data($values, $field);

			/**
			 * It is worth mentioning, that we save the data in one field,
			 * and the field name in other one for compatibility reasons.
			 *
			 * We don't need this optimization on the back end, but it is
			 * substantial on the front end. ACF plugin should be able to
			 * get the correct field key to process the result correctly
			 */
			update_metadata($entity['type'], $entity['id'], $field['name'], $value);
			update_metadata($entity['type'], $entity['id'], '_' . $field['name'], $field['key']);

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


/*
 * Encode ACF repeaters and field groups to an array
 */
function tw_acf_encode_data($values, $field) {

	if (!is_array($values) or !is_array($field)) {
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

				if ($processed) {
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

				$sub_field = $fields[$field_key];

				if (!empty($sub_field['name'])) {

					$processed = tw_acf_encode_data($value, $sub_field);

					if ($processed) {
						$data[$sub_field['name']] = $processed;
					}

				}

			}

		} else {

			$index = 0;

			foreach ($values as $row) {

				foreach ($row as $field_key => $value) {

					$sub_field = $fields[$field_key];

					if (!empty($sub_field['name'])) {

						$processed = tw_acf_encode_data($value, $sub_field);

						if ($processed) {
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