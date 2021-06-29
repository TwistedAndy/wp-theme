<?php

/**
 * Add the max-width property to the default caption shortcode and fix its width
 */
add_filter('img_caption_shortcode', 'tw_filter_caption', 20, 3);

function tw_filter_caption($value = false, $attr = [], $content = '') {

	$atts = shortcode_atts([
		'id' => '',
		'align' => 'alignnone',
		'width' => '',
		'caption' => '',
		'class' => '',
	], $attr, 'caption');

	$atts['width'] = intval($atts['width']);

	if ($atts['width'] < 1 or empty($atts['caption'])) {
		return $content;
	}

	if (!empty($atts['id'])) {
		$atts['id'] = 'id="' . esc_attr(sanitize_html_class($atts['id'])) . '" ';
	}

	$atts['class'] = 'class="' . trim('wp-caption ' . $atts['align'] . ' ' . $atts['class']) . '" ';

	$style = 'style="max-width: ' . intval($atts['width']) . 'px;"';

	return '<div ' . $atts['id'] . $atts['class'] . $style . '>' . do_shortcode($content) . '<p class="wp-caption-text">' . $atts['caption'] . '</p></div>';

}


/**
 * Remove additional image sizes
 */
add_filter('intermediate_image_sizes', 'tw_filter_image_sizes', 20);

function tw_filter_image_sizes($sizes) {
	return array_diff($sizes, ['medium_large', '1536x1536', '2048x2048']);
}


/**
 * Add SVG support
 */
add_filter('upload_mimes', 'tw_filter_svg_upload', 20);

function tw_filter_svg_upload($mimes) {

	$mimes['svg'] = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';

	return $mimes;
}


add_filter('wp_check_filetype_and_ext', 'tw_filter_svg_check', 10, 4);

function tw_filter_svg_check($checked, $file, $filename, $mimes) {

	if (empty($checked['type'])) {

		$check_filetype = wp_check_filetype($filename, $mimes);
		$ext = $check_filetype['ext'];
		$type = $check_filetype['type'];
		$proper_filename = $filename;

		if ($type and strpos($type, 'image/') === 0 and $ext !== 'svg') {
			$type = false;
			$ext = false;
		}

		$checked = compact('ext', 'type', 'proper_filename');

	}

	return $checked;

}


add_filter('wp_prepare_attachment_for_js', 'tw_filter_svg_preview', 10, 3);

function tw_filter_svg_preview($response, $attachment, $meta) {

	if ($response['mime'] == 'image/svg+xml') {

		$possible_sizes = apply_filters('image_size_names_choose', [
			'full' => __('Full Size'),
			'thumbnail' => __('Thumbnail'),
			'medium' => __('Medium'),
			'large' => __('Large'),
		]);

		$sizes = [];

		foreach ($possible_sizes as $size => $label) {

			$default_height = 300;
			$default_width = 300;

			$sizes[$size] = [
				'height' => get_option($size . '_size_w', $default_height),
				'width' => get_option($size . '_size_h', $default_width),
				'url' => $response['url'],
				'orientation' => 'portrait',
			];

		}

		$response['sizes'] = $sizes;
		$response['icon'] = $response['url'];

	}

	return $response;

}


add_filter('wp_generate_attachment_metadata', 'tw_filter_svg_metadata', 10, 2);

function tw_filter_svg_metadata($metadata, $attachment_id) {

	global $_wp_additional_image_sizes;

	$mime = get_post_mime_type($attachment_id);

	$file = get_attached_file($attachment_id);

	if ($mime == 'image/svg+xml' and file_exists($file)) {

		$upload_dir = wp_upload_dir();

		$filename = basename($file);
		$relative_path = str_replace($upload_dir['basedir'], '', $file);

		$contents = file_get_contents($file);

		$width = 0;
		$height = 0;

		$reg_width = '#<svg[^>]+?width="([^"]*?)"[^>]*?>#is';
		$reg_height = '#<svg[^>]+?height="([^"]*?)"[^>]*?>#is';
		$reg_viewport = '#<svg[^>]+?viewBox="0 0 ([0-9]+) ([0-9]+)"[^>]*?>#is';

		preg_match($reg_width, $contents, $matches);

		if ($matches and !empty($matches[1])) {
			$width = intval($matches[1]);
		}

		preg_match($reg_height, $contents, $matches);

		if ($matches and !empty($matches[1])) {
			$height = intval($matches[1]);
		}

		if (empty($width) or empty($height)) {

			preg_match($reg_viewport, $contents, $matches);

			if ($matches and !empty($matches[1]) and !empty($matches[2])) {
				$width = intval($matches[1]);
				$height = intval($matches[2]);
			}

		}

		if (empty($width) or empty($height)) {
			return $metadata;
		}

		$metadata = [
			'width' => $width,
			'height' => $height,
			'file' => $relative_path
		];

		$sizes = [];

		foreach (get_intermediate_image_sizes() as $size) {

			$data = [
				'width' => get_option($size . '_size_w'),
				'height' => get_option($size . '_size_h'),
				'crop' => get_option($size . '_crop')
			];

			foreach ($data as $key => $value) {
				if (isset($_wp_additional_image_sizes[$size]) and isset($_wp_additional_image_sizes[$size][$key])) {
					$data[$key] = $_wp_additional_image_sizes[$size][$key];
				}
			}

			$data['file'] = $filename;
			$data['mime-type'] = 'image/svg+xml';

			$sizes[$size] = $data;

		}

		$metadata['sizes'] = $sizes;

	}

	return $metadata;
}