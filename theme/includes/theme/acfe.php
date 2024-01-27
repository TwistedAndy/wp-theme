<?php
/**
 * Integration with the ACF Extended plugin
 * and other ACF teaks and customizations
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
 * Disable some ACF Extended fields and modules
 */
add_action('acf/init', function() {

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

}, 5);


/**
 * Disable some layout settings
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
 * Render a block preview
 */
add_action('acfe/flexible/render/before_template', function($field, $layout) {

	if (is_array($layout) and !empty($layout['name'])) {

		$block = get_row(true);

		if (is_array($block)) {

			$content = tw_block_render($block);

			/**
			 * Fix an issue with the embedded forms
			 */
			$content = str_replace(['<form ', '</form>'], ['<div ', '</div>'], $content);

			echo '<div id="tw">' . $content . '</div>';

		}

	}

}, 10, 2);


/**
 * Include additional scripts and styles
 */
add_action('acf/input/admin_enqueue_scripts', function() {

	global $post_type;

	if ($post_type == 'product') {
		wp_enqueue_script('jquery-core');
		wp_enqueue_script('jquery-ui-core');
	}

	?>

	<script>
		document.addEventListener('DOMContentLoaded', function() {

			/**
			 * Process the TinyMCE settings
			 */
			if (typeof acf === 'undefined') {
				return;
			}

			new acf.Model({

				actions: {
					'new_field/type=wysiwyg': 'newEditor',
					'wysiwyg_tinymce_init': 'editorInit',
				},

				filters: {
					'wysiwyg_tinymce_settings': 'editorSettings',
				},

				newEditor: function(field) {

					var height = 0;

					if (field.has('wysiwygAutoresize') && field.has('wysiwygMinHeight')) {
						height = field.get('wysiwygMinHeight');
					} else if (field.has('wysiwygHeight')) {
						height = field.get('wysiwygHeight');
					}

					if (height > 0) {
						field.$input().css('height', height);
					}

				},

				editorSettings: function(init, id, field) {

					if (field.has('wysiwygAutoresize')) {

						init.wp_autoresize_on = true;

						if (field.has('wysiwygMinHeight')) {
							init.autoresize_min_height = field.get('wysiwygMinHeight');
						}

						if (field.has('wysiwygMaxHeight')) {

							if (!field.has('wysiwygMinHeight')) {
								init.autoresize_min_height = field.get('wysiwygMaxHeight');
							}

							init.autoresize_max_height = field.get('wysiwygMaxHeight');

						}

					} else if (field.has('wysiwygHeight')) {

						var height = field.get('wysiwygHeight');

						init.min_height = height;
						init.height = height;

					}

					if (field.has('wysiwygValidElements')) {
						init.valid_elements = field.get('wysiwygValidElements');
					}

					return init;

				},

				editorInit: function(editor, editor_id, init, field) {
					if (field.has('wysiwygHeight')) {
						field.$el.find('iframe').css({
							'min-height': field.get('wysiwygHeight'),
							'height': field.get('wysiwygHeight')
						});
					}
				}

			});

			/**
			 * Initialize scripts in rendered sections
			 */
			acf.addAction('acfe/fields/flexible_content/preview', function(response, $el) {
				$el.find('[class*="_box"]').trigger('tw_init', [jQuery]);
			});

		});
	</script>

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

		.mce-toolbar .mce-ico.mce-i-table,
		.mce-toolbar .mce-ico.mce-i-paste {
			line-height: 20px;
		}
	</style>

<?php });


/**
 * Enqueue assets to preview blocks
 */
add_action('acf/render_field/type=flexible_content', function() {
	wp_enqueue_style('tw_blocks', TW_URL . 'assets/build/preview.css');
	wp_enqueue_script('tw_blocks', TW_URL . 'assets/build/scripts.js');
});


/**
 * Register additional TinyMCE plugins
 */
add_action('mce_external_plugins', function($plugins) {
	$plugins['table'] = TW_URL . 'assets/plugins/tinymce/table.js';
	$plugins['paste'] = TW_URL . 'assets/plugins/tinymce/paste.js';
	$plugins['code'] = TW_URL . 'assets/plugins/tinymce/code.js';
	return $plugins;
});


/**
 * Include additional buttons to the ACF WYSIWYG field
 */
add_action('acf/fields/wysiwyg/toolbars', function($toolbars) {

	$toolbars['Basic'] = [
		1 => [
			'formatselect',
			'link',
			'bold',
			'italic',
			'underline',
			'blockquote',
			'|',
			'bullist',
			'numlist',
			'alignleft',
			'aligncenter',
			'alignright',
			'alignjustify',
			'|',
			'code',
			'table',
			'wp_add_media'
		]
	];

	return $toolbars;

});


/**
 * Add buttons to the default WordPress editor
 */
add_action('mce_buttons', function($buttons) {
	array_push($buttons, 'table', 'code', 'paste', 'pastetext', 'wp_add_media');
	return $buttons;
});


/**
 * Process popup source code editor
 */
add_action('wp_ajax_wysiwyg_code_editor', function() {

	$settings = wp_get_code_editor_settings(['type' => 'text/html']);

	$settings['codemirror']['indentUnit'] = 4;
	$settings['codemirror']['indentWithTabs'] = true;
	$settings['codemirror']['styleActiveLine'] = false;
	$settings['codemirror']['extraKeys']['Tab'] = 'indentMore';
	$settings['codemirror']['extraKeys']['Shift-Tab'] = 'indentLess';

	$settings['jshint']['globals']['jQuery'] = true;

	wp_add_inline_script('code-editor', sprintf('jQuery.extend( wp.codeEditor.defaultSettings, %s );', wp_json_encode($settings)));

	?><!DOCTYPE html>
	<html <?php echo get_language_attributes('html'); ?>>
	<head>
		<meta charset="<?php bloginfo('charset'); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<?php wp_print_styles('code-editor') ?>
		<?php wp_print_scripts(['code-editor', 'htmlhint', 'csslint', 'jshint', 'htmlhint-kses']); ?>
		<style>
			html, body {
				height: 100%;
				margin: 0;
				padding: 0;
			}

			.CodeMirror {
				height: 100%;
				font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
				font-size: 14px;
				line-height: 1.4;
			}

			.CodeMirror-selected {
				background: #f0f0f0;
			}

			.CodeMirror-gutters {
				background: #f9f9f9;
			}
		</style>
		<script>
			var codemirror,
				editor = parent.tinymce.activeEditor,
				source = parent.document.getElementById(editor.id);

			document.addEventListener('DOMContentLoaded', function() {
				const textarea = document.body.querySelector('textarea');
				textarea.value = source.value;
				codemirror = wp.codeEditor.initialize(textarea, wp.codeEditor.defaultSettings);
			});

			document.addEventListener('keydown', function(e) {
				if (e.keyCode === 27) {
					editor.windowManager.close();
				}
			});

			function submit() {
				parent.window.switchEditors.go(editor.id);
				source.value = codemirror.codemirror.getValue();
				parent.window.switchEditors.go(editor.id);
			}
		</script>
	</head>
	<body>
	<textarea></textarea>
	</body>
	</html><?php
	exit();
});


/**
 * Replace the default ACF WYSIWYG field with the updated one
 */
add_action('acf/init', function() {

	if (!class_exists('acfe_field_extend')) {
		return;
	}

	class twee_field_wysiwyg extends acfe_field_extend {

		public function initialize() {

			$this->name = 'wysiwyg';

			$this->defaults = [
				'wysiwyg_auto_init' => 1,
				'wysiwyg_height' => 150,
				'wysiwyg_min_height' => 150,
				'wysiwyg_max_height' => '',
				'wysiwyg_valid_tags' => '',
				'wysiwyg_autoresize' => 0
			];

		}

		/**
		 * Include additional fields for the WYSIWYG editor
		 *
		 * @param array $field
		 *
		 * @return void
		 */
		public function render_field_settings($field) {

			acf_render_field_setting($field, [
				'label' => __('Auto Initialization', 'twee'),
				'name' => 'wysiwyg_auto_init',
				'type' => 'true_false',
				'default_value' => false,
				'ui' => true,
				'conditional_logic' => [
					[
						[
							'field' => 'delay',
							'operator' => '==',
							'value' => '1',
						],
					],
				]
			]);

			acf_render_field_setting($field, [
				'label' => __('Autoresize', 'twee'),
				'name' => 'wysiwyg_autoresize',
				'key' => 'wysiwyg_autoresize',
				'type' => 'true_false',
				'default_value' => true,
				'ui' => true,
			]);

			acf_render_field_setting($field, [
				'label' => __('Height', 'twee'),
				'name' => 'wysiwyg_height',
				'key' => 'wysiwyg_height',
				'type' => 'number',
				'default_value' => 200,
				'min' => 80,
				'conditional_logic' => [
					[
						[
							'field' => 'wysiwyg_autoresize',
							'operator' => '!=',
							'value' => '1',
						],
					]
				]
			]);

			acf_render_field_setting($field, [
				'label' => __('Height', 'twee'),
				'name' => 'wysiwyg_min_height',
				'key' => 'wysiwyg_min_height',
				'type' => 'number',
				'default_value' => 200,
				'min' => 80,
				'prepend' => 'min',
				'append' => 'px',
				'conditional_logic' => [
					[
						[
							'field' => 'wysiwyg_autoresize',
							'operator' => '==',
							'value' => '1',
						],
					]
				]
			]);

			acf_render_field_setting($field, [
				'label' => __('Height', 'twee'),
				'name' => 'wysiwyg_max_height',
				'key' => 'wysiwyg_max_height',
				'instructions' => '',
				'type' => 'number',
				'default_value' => '',
				'min' => 0,
				'prepend' => 'max',
				'append' => 'px',
				'_append' => 'wysiwyg_min_height',
				'conditional_logic' => [
					[
						[
							'field' => 'wysiwyg_autoresize',
							'operator' => '==',
							'value' => '1',
						],
					]
				]
			]);

			acf_render_field_setting($field, [
				'label' => __('Valid Tags', 'twee'),
				'name' => 'wysiwyg_valid_tags',
				'key' => 'wysiwyg_valid_tags',
				'type' => 'text',
				'placeholder' => __('A comma-separated list of tags', 'twee'),
				'wrapper' => [
					'data-enable-switch' => true
				]
			]);

		}

		/**
		 * Include field settings to a wrapper
		 *
		 * @param array $wrapper
		 * @param array $field
		 *
		 * @return array
		 */
		public function field_wrapper_attributes($wrapper, $field) {

			if (!empty($field['wysiwyg_autoresize'])) {

				$wrapper['data-wysiwyg-autoresize'] = 1;

				if (isset($field['wysiwyg_min_height']) and is_numeric($field['wysiwyg_min_height'])) {
					$wrapper['data-wysiwyg-min-height'] = $field['wysiwyg_min_height'];
				}

				if (isset($field['wysiwyg_max_height']) and is_numeric($field['wysiwyg_max_height'])) {
					$wrapper['data-wysiwyg-max-height'] = $field['wysiwyg_max_height'];
				}

			} elseif (isset($field['wysiwyg_height']) and is_numeric($field['wysiwyg_height'])) {

				$wrapper['data-wysiwyg-height'] = $field['wysiwyg_height'];

			}

			if (!empty($field['wysiwyg_valid_tags'])) {
				$wrapper['data-wysiwyg-valid-tags'] = $field['wysiwyg_valid_tags'];
			}

			return $wrapper;

		}

	}

	acf_new_instance('twee_field_wysiwyg');

});


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