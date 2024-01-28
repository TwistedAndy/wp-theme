<?php
/**
 * Integration with ACF Extended and other customizations:
 * - Process preview for ACF Flexible Content field
 * - Add support for WooCommerce product variations
 * - A fallback for the get_field() function
 * - Create an option page for settings
 * - Disable some ACF Extended modules
 * - Enable the ACF Local JSON sync
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

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
 * A few early ACF tweaks
 */
add_action('acf/init', function() {

	/**
	 * Disable some ACF Extended fields and modules
	 */
	acf_update_setting('acfe/modules/classic_editor', true);

	acf_update_setting('acfe/dev', false);
	acf_update_setting('acfe/php', false);
	acf_update_setting('acfe/modules/author', false);
	acf_update_setting('acfe/modules/block_types', false);
	acf_update_setting('acfe/modules/categories', false);
	acf_update_setting('acfe/modules/force_sync', false);
	acf_update_setting('acfe/modules/forms', false);
	acf_update_setting('acfe/modules/multilang', false);
	acf_update_setting('acfe/modules/screen_layouts', false);
	acf_update_setting('acfe/modules/options_pages', false);
	acf_update_setting('acfe/modules/post_types', false);
	acf_update_setting('acfe/modules/taxonomies', false);
	acf_update_setting('acfe/modules/scripts', false);
	acf_update_setting('acfe/modules/templates', false);
	acf_update_setting('acfe/modules/performance', '');

	$fields = acf()->fields;

	if ($fields instanceof acf_fields) {

		$types = [
			'acfe_payment',
			'acfe_payment_cart',
			'acfe_payment_selector',
			'acfe_block_types',
			'acfe_field_groups',
			'acfe_field_types',
			'acfe_fields',
			'acfe_forms',
			'acfe_options_pages',
			'acfe_templates',
			'acfe_image_sizes',
			'acfe_menu_locations',
			'acfe_menus',
			'acfe_post_formats',
			'acfe_post_statuses',
			'acfe_post_types',
			'acfe_taxonomies',
			'acfe_user_roles',
			'acfe_advanced_link',
			'acfe_countries',
			'acfe_currencies',
			'acfe_languages',
			'acfe_button',
			'acfe_recaptcha',
			'acfe_post_field',
		];

		foreach ($types as $type) {
			unset($fields->types[$type]);
		}

	}

	/**
	 * Add a new options page for the theme settings
	 */
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

}, 5);


/**
 * Disable a few layout settings
 */
add_action('acf/init', function() {
	remove_all_actions('acfe/flexible/render_layout_settings', 15);
	remove_all_actions('acfe/flexible/render_layout_settings', 19);
}, 100);


/**
 * Specify the API key for the Google Maps field
 */
add_filter('acf/settings/google_api_key', function() {
	return get_option('options_google_api_key', base64_decode('QUl6YVN5QUo1UVRzajRhcFNuVkstNlQ3SE1RZlVXNS1SbGpKVFE0'));
});


/**
 * Render a block preview with required scripts
 */
add_action('acfe/flexible/render/before_template', function($field, $layout) {

	if (!is_array($layout) or empty($layout['name'])) {
		return;
	}

	tw_asset_autoload(false);

	$block = get_row(true);

	if (!is_array($block)) {
		return;
	}

	$preview_id = 'tw_' . rand(0, 100000);

	/**
	 * Reset the currently enqueued scripts
	 * to include them again in an iframe
	 */
	$assets_printed = tw_asset_list('printed');
	$assets_enqueued = tw_asset_list('enqueued');
	$object_scripts = wp_scripts();
	$object_styles = wp_styles();
	$done_scripts = $object_scripts->done;
	$done_styles = $object_styles->done;

	$object_scripts->done = [];
	$object_styles->done = [];

	tw_asset_list('printed', []);
	tw_asset_list('enqueued', []);

	ob_start();

	?><!DOCTYPE html>
	<html <?php echo get_language_attributes('html'); ?>>
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php tw_asset_print(); ?>
	</head>
	<body <?php body_class(); ?> style="overflow: hidden;">
		<main><?php echo tw_block_render($block); ?></main>
	<?php tw_asset_print(); ?>
	<script>

		function resizeObserver(element, callback) {

			let last = element.getBoundingClientRect();

			const observer = new ResizeObserver(function() {
				const data = element.getBoundingClientRect();
				if (data.height !== last.height || data.width !== last.width) {
					callback();
					last = data;
				}
			});

			observer.observe(element);

		}

		function triggerResize() {
			window.parent.postMessage({
				type: 'resize',
				frame: '<?php echo $preview_id; ?>',
				height: document.body.scrollHeight
			}, '*');
		}

		window.addEventListener('load', triggerResize);
		window.addEventListener('resize', triggerResize);

		document.querySelectorAll('main').forEach(function(section) {
			resizeObserver(section, triggerResize);
		});

		triggerResize();

	</script>
	</body>
	</html><?php

	$content = ob_get_clean();

	tw_asset_autoload(true);

	tw_asset_list('printed', $assets_printed);
	tw_asset_list('enqueued', $assets_enqueued);

	if (strpos($content, 'gform_wrapper') > 0 and class_exists('GFForms')) {
		$content = GFForms::ensure_hook_js_output($content);
	}

	$object_scripts->done = $done_scripts;
	$object_styles->done = $done_styles;

	?>
	<div style="display: none;"><?php echo htmlspecialchars($content); ?></div>
	<iframe style="display: block; width: 100%; position: relative; z-index: 1;" id="<?php echo $preview_id; ?>" onload="tweePreviewBlock(this);"></iframe>
<?php }, 10, 2);


/**
 * Include scripts to render blocks in separate
 * iframes with the automatic height adjustment
 */
add_action('acf/input/admin_enqueue_scripts', function() { ?>
	<script>

		function tweePreviewBlock(frame) {

			var contents = frame.contentWindow.document,
				field = document.createElement('textarea');

			field.innerHTML = frame.previousElementSibling.innerText;

			contents.open();
			contents.write(field.value);
			contents.close();

			window.addEventListener('resize', function() {
				frame.style.height = Math.ceil(frame.contentWindow.document.body.scrollHeight) + 'px';
			});

		}

		window.addEventListener('message', function(e) {
			if (e.data && e.data.type === 'resize' && e.data.frame && e.data.height) {
				document.getElementById(e.data.frame).style.height = Math.ceil(e.data.height) + 'px';
			}
		});

		document.addEventListener('DOMContentLoaded', function() {

			if (typeof acf === 'undefined') {
				return;
			}

			acf.addAction('acfe/fields/flexible_content/before_preview', function(response, element) {
				var preview = jQuery('.acfe-flexible-placeholder', element);
				if (preview.length > 0) {
					preview.css('height', Math.ceil(preview.height()));
				}
			});

			acf.addAction('acfe/fields/flexible_content/preview', function(response, element) {
				setTimeout(function() {
					jQuery('.acfe-flexible-placeholder', element).removeAttr('style');
				}, 250);
			});

		});

	</script>
<?php });


/**
 * Add support for WooCommerce product variations
 */
if (class_exists('WooCommerce')) {

	/**
	 * Include a few UI components for variable products
	 */
	add_action('admin_head', function() {

		global $post_type;

		if ($post_type == 'product') {
			wp_enqueue_script('jquery-core');
			wp_enqueue_script('jquery-ui-core');
		}

	});

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