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
 * Add a field visibility setting
 */
add_action('acf/render_field_settings', function($field) {

	acf_render_field_setting($field, [
		'label' => __('Visibility', 'twee'),
		'instructions' => '',
		'name' => 'hide_label',
		'prepend' => '',
		'append' => '',
		'type' => 'select',
		'default_value' => '',
		'allow_null' => false,
		'choices' => [
			'' => 'Visible',
			'all' => 'Hidden',
			'admin' => 'Hidden in WP Admin',
			'front' => 'Hidden on Front',
		],
		'_append' => 'label'
	], true);

});


/**
 * Include scripts to render blocks in separate
 * iframes with the automatic height adjustment
 */
add_action('acf/input/admin_enqueue_scripts', 'tw_acfe_render_scripts', 20);

function tw_acfe_render_scripts() { ?>
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
<?php }


/**
 * Render an ACF layout preview with required scripts
 */
add_action('acfe/flexible/render/before_template', 'tw_acfe_render_layout', 10, 2);

function tw_acfe_render_layout($field, $layout) {

	if (!is_array($layout) or empty($layout['name'])) {
		return;
	}

	$block = get_row(true);

	if (!is_array($block)) {
		return;
	}

	$preview_id = 'tw_' . rand(0, 100000);

	tw_acfe_render_setup();

	$content = tw_app_template('layout', ['preview_id' => $preview_id, 'block' => $block]);

	$content = tw_asset_inject($content);

	tw_acfe_render_reset();

	if (strpos($content, 'gform_wrapper') > 0 and class_exists('GFForms')) {
		$content = GFForms::ensure_hook_js_output($content);
	}

	$content = str_replace(["\n", "\r", "\t"], '', $content);

	echo '<div style="display: none;">' . htmlspecialchars($content) . '</div>';
	echo '<iframe style="display: block; width: 100%; position: relative; z-index: 1;" id="' . $preview_id . '" onload="tweePreviewBlock(this);"></iframe>';

}


/**
 * Setup the global variables for rendering
 *
 * @return void
 */
function tw_acfe_render_setup() {

	global $wp_query, $wp_the_query;

	if (tw_app_get('new_query', 'layouts')) {

		$wp_query = tw_app_get('new_query', 'layouts');
		$wp_the_query = $wp_query;

	} else {

		$old_query = $wp_query;
		$old_the_query = $wp_the_query;
		$query_args = [];

		if (!empty($_REQUEST['post_id'])) {
			$entity = tw_acf_decode_post_id($_REQUEST['post_id']);
		} elseif (!empty($_REQUEST['post']) and is_numeric($_REQUEST['post'])) {
			$entity = [
				'id' => (int) $_REQUEST['post'],
				'type' => 'post'
			];
		} elseif (!empty($_REQUEST['tag_ID']) and is_numeric($_REQUEST['tag_ID'])) {
			$entity = [
				'id' => (int) $_REQUEST['tag_ID'],
				'type' => 'term'
			];
		} else {
			$entity = [];
		}

		if (!empty($entity['type']) and !empty($entity['id']) and is_numeric($entity['id'])) {

			if ($entity['type'] == 'post' and $post = get_post($entity['id'])) {

				if ($entity['id'] == get_option('woocommerce_shop_page_id', 0) and function_exists('WC')) {

					if ($query = WC()->query and !has_action('pre_get_posts', [$query, 'pre_get_posts'])) {
						add_filter('query_vars', [WC()->query, 'add_query_vars'], 0);
						add_action('parse_request', [WC()->query, 'parse_request'], 0);
						add_action('pre_get_posts', [WC()->query, 'pre_get_posts']);
					}

					$query_args = [
						'post_type' => 'product'
					];

				} else {
					$query_args = [
						'p' => $post->ID,
						'post_type' => $post->post_type
					];
				}

			} elseif ($entity['type'] == 'term' and $term = get_term($entity['id'])) {

				$query_args = [
					'tax_query' => [
						[
							'taxonomy' => $term->taxonomy,
							'terms' => [$term->slug],
							'field' => 'slug'
						]
					]
				];

			} elseif ($entity['type'] == 'user') {

				$query_args = [
					'author' => $entity['id']
				];

			}
		}

		if ($query_args) {
			$wp_query = new WP_Query();
			$wp_the_query = $wp_query;
			$wp_query->query($query_args);
		}

		tw_app_set('new_query', $wp_query, 'layouts');
		tw_app_set('old_query', $old_query, 'layouts');
		tw_app_set('old_the_query', $old_the_query, 'layouts');

	}

	/**
	 * Reset the currently enqueued scripts
	 * to include them again in an iframe
	 */
	$assets_printed = tw_app_get('printed', 'assets', []);
	$assets_enqueued = tw_app_get('enqueued', 'assets', []);
	$object_scripts = wp_scripts();
	$object_styles = wp_styles();
	$done_scripts = $object_scripts->done;
	$done_styles = $object_styles->done;

	$object_scripts->done = [];
	$object_styles->done = [];

	tw_app_set('printed', [], 'assets');
	tw_app_set('enqueued', [], 'assets');
	tw_app_set('assets_printed', $assets_printed, 'layouts');
	tw_app_set('assets_enqueued', $assets_enqueued, 'layouts');
	tw_app_set('done_scripts', $done_scripts, 'layouts');
	tw_app_set('done_styles', $done_styles, 'layouts');

}


/**
 * Reset the global variables
 *
 * @return void
 */
function tw_acfe_render_reset() {

	global $wp_query, $wp_the_query;

	$object_scripts = wp_scripts();
	$object_styles = wp_styles();

	$assets_printed = tw_app_get('assets_printed', 'layouts', []);
	$assets_enqueued = tw_app_get('assets_enqueued', 'layouts', []);
	$done_scripts = tw_app_get('done_scripts', 'layouts', []);
	$done_styles = tw_app_get('done_styles', 'layouts', []);

	if (is_array($assets_printed)) {
		tw_app_set('printed', $assets_printed, 'assets');
	}

	if (is_array($assets_enqueued)) {
		tw_app_set('enqueued', $assets_enqueued, 'assets');
	}

	$object_scripts->done = $done_scripts;
	$object_styles->done = $done_styles;

	if (tw_app_get('old_query', 'layouts')) {
		$wp_query = tw_app_get('old_query', 'layouts');
		$wp_the_query = tw_app_get('old_the_query', 'layouts');
		wp_reset_postdata();
	}

}


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