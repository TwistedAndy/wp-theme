<?php
/**
 * Improve the default WYSIWYG ACF field:
 * - Add an automatic height adjustment
 * - Syntax highlighting for sources
 * - A table insertion tool
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */

/**
 * Register additional TinyMCE plugins
 */
function tw_tinymce_plugins(array $plugins): array
{
	$plugins['table'] = TW_URL . 'assets/plugins/tinymce/table.js';
	$plugins['paste'] = TW_URL . 'assets/plugins/tinymce/paste.js';
	$plugins['code'] = TW_URL . 'assets/plugins/tinymce/code.js';

	return $plugins;
}

add_action('mce_external_plugins', 'tw_tinymce_plugins');


/**
 * Include additional buttons to the ACF WYSIWYG field
 */
function tw_tinymce_toolbars(array $toolbars): array
{
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
}

add_action('acf/fields/wysiwyg/toolbars', 'tw_tinymce_toolbars');


/**
 * Add buttons to the default WordPress editor
 */
function tw_tinymce_buttons(array $buttons): array
{
	array_push($buttons, 'table', 'code', 'paste', 'pastetext', 'wp_add_media');

	return $buttons;
}
add_action('mce_buttons', 'tw_tinymce_buttons');


/**
 * Replace the default ACF WYSIWYG field with the updated one
 */
function tw_tinymce_init(): void
{
	if (!class_exists('acfe_field_extend')) {
		return;
	}

	class twee_field_wysiwyg extends acfe_field_extend {

		public function initialize(): void
		{
			$this->name = 'wysiwyg';

			$this->defaults = [
				'wysiwyg_auto_init'  => 1,
				'wysiwyg_height'     => 150,
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
		public function render_field_settings(array $field): void
		{
			acf_render_field_setting($field, [
				'label'             => __('Auto Initialization', 'twee'),
				'name'              => 'wysiwyg_auto_init',
				'type'              => 'true_false',
				'default_value'     => false,
				'ui'                => true,
				'conditional_logic' => [
					[
						[
							'field'    => 'delay',
							'operator' => '==',
							'value'    => '1',
						],
					],
				]
			]);

			acf_render_field_setting($field, [
				'label'         => __('Autoresize', 'twee'),
				'name'          => 'wysiwyg_autoresize',
				'key'           => 'wysiwyg_autoresize',
				'type'          => 'true_false',
				'default_value' => true,
				'ui'            => true,
			]);

			acf_render_field_setting($field, [
				'label'             => __('Height', 'twee'),
				'name'              => 'wysiwyg_height',
				'key'               => 'wysiwyg_height',
				'type'              => 'number',
				'default_value'     => 100,
				'min'               => 80,
				'conditional_logic' => [
					[
						[
							'field'    => 'wysiwyg_autoresize',
							'operator' => '!=',
							'value'    => '1',
						],
					]
				]
			]);

			acf_render_field_setting($field, [
				'label'             => __('Height', 'twee'),
				'name'              => 'wysiwyg_min_height',
				'key'               => 'wysiwyg_min_height',
				'type'              => 'number',
				'default_value'     => 150,
				'min'               => 80,
				'prepend'           => 'min',
				'append'            => 'px',
				'conditional_logic' => [
					[
						[
							'field'    => 'wysiwyg_autoresize',
							'operator' => '==',
							'value'    => '1',
						],
					]
				]
			]);

			acf_render_field_setting($field, [
				'label'             => __('Height', 'twee'),
				'name'              => 'wysiwyg_max_height',
				'key'               => 'wysiwyg_max_height',
				'instructions'      => '',
				'type'              => 'number',
				'default_value'     => '',
				'min'               => 0,
				'prepend'           => 'max',
				'append'            => 'px',
				'_append'           => 'wysiwyg_min_height',
				'conditional_logic' => [
					[
						[
							'field'    => 'wysiwyg_autoresize',
							'operator' => '==',
							'value'    => '1',
						],
					]
				]
			]);

			acf_render_field_setting($field, [
				'label'       => __('Valid Tags', 'twee'),
				'name'        => 'wysiwyg_valid_tags',
				'key'         => 'wysiwyg_valid_tags',
				'type'        => 'text',
				'placeholder' => __('A comma-separated list of tags', 'twee'),
				'wrapper'     => [
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
		public function field_wrapper_attributes(array $wrapper, array $field): array
		{

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

}

add_action('acf/init', 'tw_tinymce_init');


/**
 * Process popup source code editor
 */
function tw_tinymce_editor(): void
{
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
	</html>
<?php }

add_action('wp_ajax_wysiwyg_code_editor', 'tw_tinymce_editor');


/**
 * Include additional scripts and styles
 */
function tw_tinymce_scripts()
{ ?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			if (typeof acf === 'undefined') {
				return;
			}

			new acf.Model({

				actions: {
					'new_field/type=wysiwyg': 'newEditor',
					'wysiwyg_tinymce_init': 'editorInit'
				},

				filters: {
					'wysiwyg_tinymce_settings': 'editorSettings'
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
		});
	</script>
	<style>
		.mce-toolbar .mce-ico.mce-i-table,
		.mce-toolbar .mce-ico.mce-i-paste {
			line-height: 20px;
		}

		.acfe-modal-content > .acf-fields {
			padding: 8px;
		}

		.acfe-modal.-open {
			margin: 0 !important;
		}

		.layout > .acfe-fc-placeholder.acfe-fc-preview > a {
			width: 50px;
			height: 50px;
			z-index: 5;
		}

		.layout > .acfe-fc-placeholder.acfe-fc-preview > a span {
			display: flex;
			align-items: center;
			justify-content: center;
			line-height: 1;
			font-size: 24px;
			width: 100%;
			height: 100%;
			padding-left: 1px;
		}
	</style>
<?php }

add_action('acf/input/admin_enqueue_scripts', 'tw_tinymce_scripts');