<?php
/**
 * Add a slider and a video options to the default WP gallery
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.3
 */

/**
 * Add a slider control to WP Gallery
 */
function tw_gallery_controls(): void
{ ?>
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
<?php }

add_action('print_media_templates', 'tw_gallery_controls');


/**
 * Change the default link type to file
 */
function tw_gallery_link_type(array $settings): array
{
	$settings['galleryDefaults']['link'] = 'file';
	$settings['defaultProps']['link'] = 'file';

	return $settings;
}

add_filter('media_view_settings', 'tw_gallery_link_type');


/**
 * Add a video field
 */
function tw_gallery_fields(array $fields, WP_Post $post): array
{
	$fields['video_link'] = [
		'label' => __('Video URL', 'twee'),
		'input' => 'text',
		'value' => (string) tw_meta_get('post', $post->ID, 'video_link'),
		'helps' => __('A link on YouTube, Vimeo, or a MP4 file', 'twee')
	];

	return $fields;
}

add_filter('attachment_fields_to_edit', 'tw_gallery_fields', 10, 2);


/**
 * Save a video field
 */
add_filter('attachment_fields_to_save', function($post, $attachment) {

	if (!is_array($post) or empty($post['ID'])) {
		return $post;
	}

	if (!empty($attachment['video_link'])) {
		tw_meta_update('post', $post['ID'], 'video_link', sanitize_text_field($attachment['video_link']));
	} else {
		tw_meta_delete('post', $post['ID'], 'video_link');
	}

	return $post;

}, 10, 2);


/**
 * Replace the gallery shortcode
 */
function tw_gallery_shortcode(string $output, array $attr): string
{
	if (empty($attr['slider'])) {
		return $output;
	}

	if (!empty($attr['include']) and empty($attr['orderby'])) {
		$attr['orderby'] = 'post__in';
	}

	$attr = shortcode_atts([
		'order'   => 'ASC',
		'orderby' => 'menu_order ID',
		'id'      => get_queried_object_id(),
		'include' => '',
		'exclude' => '',
		'link'    => '',
	], $attr, 'gallery');

	if (empty($attr['size'])) {
		$attr['size'] = 'large';
	}

	if (empty($attr['columns'])) {
		$attr['columns'] = 3;
	}

	$args = [
		'post_status'    => 'inherit',
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'order'          => $attr['order'],
		'orderby'        => $attr['orderby'],
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

	if (empty($attachments)) {
		return $output;
	}

	tw_asset_enqueue('embla');

	ob_start(); ?>

	<div class="gallery gallery-columns-<?php echo $attr['columns']; ?> carousel">

		<?php if (!empty($attr['link']) and $attr['link'] == 'file') { ?>

			<?php foreach ($attachments as $attachment) {

				$link = (string) tw_meta_get('post', $attachment->ID, 'video_link');

				if (empty($link)) {
					$link = tw_image_link($attachment->ID, 'full');
					$class = 'gallery-item';
					$after = '';
				} else {
					$class = 'gallery-item gallery-video';
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

	return ob_get_clean();

}

add_filter('post_gallery', 'tw_gallery_shortcode', 10, 2);