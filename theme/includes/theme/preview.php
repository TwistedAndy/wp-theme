<?php

/**
 * Add a thumbnail for a layout
 */
add_filter('acfe/flexible/thumbnail', function($thumbnail, $field, $layout) {

	if (!is_array($layout) or empty($layout['name'])) {
		return $thumbnail;
	}

	$file = 'assets/preview/' . $layout['name'] . '.webp';

	$preview = TW_ROOT . $file;

	if (is_readable($preview)) {
		return TW_URL . $file;
	} else {
		return $thumbnail;
	}

}, 10, 3);


/**
 * Run the code below only on the local server
 */
if (strpos(TW_HOME, '.test') === false) {
	return;
}


/**
 * Add an icon to generate a preview
 */
add_filter('acfe/flexible/layouts/icons', function($icons) {

	$new = [
		'thumbnail' => '<a class="acf-icon small light acfe-flexible-icon acf-js-tooltip dashicons dashicons-camera-alt" href="#" data-name="generate-preview" title="' . __('Generate a preview', 'acf') . '"></a>'
	];

	return array_merge($new, $icons);

}, 50);


/**
 * Process AJAX requests
 */
add_action('wp_ajax_twee_generate_preview', function() {

	$result = [
		'success' => 0,
		'message' => '',
	];

	if (!isset($_POST['id'])) {
		$result['message'] = 'Block ID is not specified';
		wp_send_json($result);
	} else {
		$_POST['id'] = (int) $_POST['id'];
	}

	if (!empty($_POST['post']) and is_numeric($_POST['post'])) {

		$post = get_post($_POST['post']);

		if ($post instanceof WP_Post and $post->post_type === 'block_set') {

			$blocks_map = tw_metadata('post', 'blocks', true);

			foreach ($blocks_map as $post_id => $blocks) {

				if (empty($blocks) or !is_array($blocks)) {
					continue;
				}

				foreach ($blocks as $index => $block) {

					if ($block['acf_fc_layout'] !== 'set' or empty($block['set'])) {
						continue;
					}

					if ((is_numeric($block['set']) and $block['set'] == $post->ID) or (is_array($block['set']) and in_array($post->ID, $block['set']))) {

						$current_post = get_post($post_id);

						if ($current_post instanceof WP_Post and $current_post->post_status == 'publish') {
							$post = $current_post;
							$_POST['id'] = $index;
							break 2;
						}

					}

				}

			}

		}

		$link = get_permalink($post);

	} elseif (!empty($_POST['term']) and is_numeric($_POST['term'])) {

		$link = get_term_link((int) $_POST['term']);

	} else {

		$link = '';

	}

	if ($link instanceof WP_Error) {
		$result['message'] = $link->get_error_message();
		wp_send_json($result);
	} elseif (empty($link)) {
		$result['message'] = 'Post is not resolved';
		wp_send_json($result);
	}

	$result = [
		'success' => 0,
		'message' => '',
	];

	$command = '"C:\Program Files\NodeJS\node.exe" "D:\Work\Theme\wp-content\themes\screens.js" "' . $link . '?preview" #block_' . $_POST['id'] . ' 2>&1';

	exec($command, $output, $result_code);

	if ($result_code === 0) {
		$result['success'] = 1;
	}

	if ($output and is_array($output)) {
		$result['message'] = implode("\n", $output);
	}

	wp_send_json($result);

});


/**
 * Include admin scripts
 */
add_action('acf/input/admin_enqueue_scripts', function() { ?>
	<script>

		document.addEventListener('DOMContentLoaded', function() {

			const $ = jQuery;

			$(document.body).on('click', '[data-name="generate-preview"]', function() {

				var button = $(this),
					layout = button.closest('[data-layout]'),
					form = layout.closest('form');

				$.ajax(ajaxurl, {
					type: 'post',
					dataType: 'json',
					data: {
						action: 'twee_generate_preview',
						post: $('[name="post_ID"]', form).val(),
						term: $('[name="tag_ID"]', form).val(),
						id: layout.data('id').replace('row-', '')
					},
					beforeSend: function() {
						button.removeClass('.acfe-flexible-icon').removeClass('dashicons-camera-alt').addClass('dashicons-update');
					}
				}).done(function(response) {
					alert(response.message);
				}).always(function() {
					button.addClass('.acfe-flexible-icon').addClass('dashicons-camera-alt').removeClass('dashicons-update');
				});

			});

		});

	</script>
	<style>
		div.acfe-flexible-layout-thumbnail {
			height: auto;
			background-size: contain;
			background-color: #ffffff;
		}

		div.acfe-flexible-layout-thumbnail:before {
			content: '';
			display: block;
			position: relative;
			padding-bottom: 62.5%;
		}

	</style>
<?php }, 20);