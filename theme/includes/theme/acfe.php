<?php
/**
 * Integration with the ACF Extended plugin
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

add_action('acf/init', function() {

	/**
	 * Enable classic editor
	 */
	acf_update_setting('acfe/modules/classic_editor', true);

	/**
	 * Disable some ACF Extended modules
	 */
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

	/**
	 * Disable some ACF Extended fields
	 */
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
 * Render a block preview
 */
add_action('acfe/flexible/render/before_template', function($field, $layout) {

	if (is_array($layout) and !empty($layout['name'])) {

		$block = get_row(true);

		if (is_array($block)) {
			echo '<div id="tw">' . tw_block_render($block) . '</div>';
		}

	}

}, 10, 2);


/**
 * Enqueue assets to preview blocks
 */
add_action('acf/render_field/type=flexible_content', function() {
	wp_enqueue_style('tw_blocks', TW_URL . 'assets/build/preview.css');
	wp_enqueue_script('tw_blocks', TW_URL . 'assets/build/scripts.js');
	?>
	<script type="text/javascript">
		jQuery(function() {
			if (typeof acf === 'object') {
				acf.addAction('acfe/fields/flexible_content/preview', function(response, $el) {
					$el.find('[class*="_box"]').trigger('tw_init', [jQuery]);
				});
			}
		});
	</script>
	<?php
});