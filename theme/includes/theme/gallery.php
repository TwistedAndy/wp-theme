<?php

/**
 * Add a slider control to WP Gallery
 */
add_action('print_media_templates', function() { ?>

	<script type="text/html" id="tmpl-twee-gallery-setting">
		<span class="setting">
			<label for="gallery-settings-slider" class="name"><?php _e('Slider', 'twee'); ?></label>
			<select id="gallery-settings-slider" data-setting="slider">
				<option value="0"><?php esc_html_e('No', 'twee'); ?></option>
				<option value="1"><?php esc_html_e('Yes', 'twee'); ?></option>
			</select>
		</span>
	</script>

	<script>

		jQuery(function() {

			_.extend(wp.media.gallery.defaults, {
				slider: '0'
			});

			wp.media.view.Settings.Gallery = wp.media.view.Settings.Gallery.extend({
				template: function(view) {
					return wp.media.template('gallery-settings')(view) + wp.media.template('twee-gallery-setting')(view);
				}
			});

		});

	</script>

<?php });


/**
 * Change the default link type to file
 */
add_filter('media_view_settings', function($settings) {
	$settings['galleryDefaults']['link'] = 'file';
	$settings['defaultProps']['link'] = 'file';
	return $settings;
});


/**
 * Add a video field
 */
add_filter('attachment_fields_to_edit', function($fields, $post) {

	$fields['video_link'] = [
		'label' => __('Video URL', 'twee'),
		'input' => 'text',
		'value' => (string) get_post_meta($post->ID, 'video_link', true),
		'helps' => __('A link on YouTube, Vimeo, or a MP4 file', 'twee')
	];

	return $fields;

}, 10, 2);


/**
 * Save a video field
 */
add_filter('attachment_fields_to_save', function($post, $attachment) {

	if (!is_array($post) or empty($post['ID'])) {
		return $post;
	}

	if (!empty($attachment['video_link'])) {
		update_post_meta($post['ID'], 'video_link', sanitize_text_field($attachment['video_link']));
	} else {
		delete_post_meta($post['ID'], 'video_link');
	}

	return $post;

}, 10, 2);


/**
 * Replace the gallery shortcode
 */
add_filter('post_gallery', function($output, $attr, $instance) {

	if (empty($attr['slider'])) {
		return $output;
	}

	tw_asset_enqueue('thumbs');

	if (!empty($attr['include']) and empty($attr['orderby'])) {
		$attr['orderby'] = 'post__in';
	}

	$attr = shortcode_atts([
		'order' => 'ASC',
		'orderby' => 'menu_order ID',
		'id' => get_queried_object_id(),
		'include' => '',
		'exclude' => '',
		'link' => '',
	], $attr, 'gallery');

	if (empty($attr['size'])) {
		$attr['size'] = 'large';
	}

	if (empty($attr['columns'])) {
		$attr['columns'] = 1;
	}

	$args = [
		'post_status' => 'inherit',
		'post_type' => 'attachment',
		'post_mime_type' => 'image',
		'order' => $attr['order'],
		'orderby' => $attr['orderby'],
	];

	if (!empty($attr['include'])) {

		$args['include'] = $attr['include'];

		$attachments = get_posts($args);

	} elseif (!empty($attr['id'])) {

		$args['post_parent'] = $attr['id'];

		if (!empty($attr['exclude'])) {
			$args['exclude'] = $attr['exclude'];
		}

		$attachments = get_children($args);

	} else {

		$attachments = [];

	}

	if ($attachments) {

		ob_start(); ?>

		<div class="gallery gallery-columns-<?php echo $attr['columns']; ?> carousel">

			<?php if (!empty($attr['link']) and $attr['link'] == 'file') { ?>

				<?php foreach ($attachments as $attachment) {

					$link = (string) get_post_meta($attachment->ID, 'video_link', true);

					if (empty($link)) {
						$link = tw_image_link($attachment->ID, 'full');
						$class = 'gallery-item';
						$after = '';
					} else {
						$class = 'gallery-item  gallery-video';
						$after = '<span class="play"></span>';
					}

					?>
					<a href="<?php echo esc_url($link); ?>" class="<?php echo $class; ?>" data-thumb-src="<?php echo tw_image_link($attachment->ID, 'thumbnail'); ?>">
						<?php echo tw_image($attachment->ID, $attr['size']) . $after; ?>
					</a>
				<?php } ?>

			<?php } else { ?>

				<?php foreach ($attachments as $attachment) { ?>
					<div class="gallery-item" data-thumb-src="<?php echo tw_image_link($attachment->ID, 'thumbnail'); ?>">
						<?php echo tw_image($attachment->ID, $attr['size']); ?>
					</div>
				<?php } ?>

			<?php } ?>

		</div>

		<?php

		$output = ob_get_clean();

	}

	return $output;

}, 10, 3);