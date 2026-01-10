<?php
/**
 * Additional filters for image processing
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */

/**
 * Add the max-width property to the default caption shortcode and fix its width
 */
function tw_images_caption_shortcode($value = false, array $attr = [], string $content = ''): string
{
	$atts = shortcode_atts([
		'id'      => '',
		'align'   => 'alignnone',
		'width'   => '',
		'caption' => '',
		'class'   => '',
	], $attr, 'caption');

	$atts['width'] = (int) $atts['width'];

	if ($atts['width'] < 1 or empty($atts['caption'])) {
		return $content;
	}

	if (!empty($atts['id'])) {
		$atts['id'] = 'id="' . esc_attr(sanitize_html_class($atts['id'])) . '" ';
	}

	$atts['class'] = 'class="' . trim('wp-caption ' . $atts['align'] . ' ' . $atts['class']) . '" ';

	$style = 'style="max-width: ' . $atts['width'] . 'px;"';

	return '<div ' . $atts['id'] . $atts['class'] . $style . '>' . do_shortcode($content) . '<p class="wp-caption-text">' . $atts['caption'] . '</p></div>';

}

add_filter('img_caption_shortcode', 'tw_images_caption_shortcode', 20, 3);


/**
 * Remove some image sizes
 */
function tw_images_filter_sizes(array $sizes): array
{
	return array_diff($sizes, ['medium_large', '1536x1536', '2048x2048']);
}

add_filter('intermediate_image_sizes', 'tw_images_filter_sizes', 20);


/**
 * Add SVG to the MIME list
 */
function tw_images_svg_mimes(array $mimes): array
{
	$mimes['svg'] = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';

	return $mimes;
}

add_filter('upload_mimes', 'tw_images_svg_mimes', 20);


/**
 * Adjust the SVG type check
 */
function tw_images_svg_type_filter(array $checked, string $file, string $proper_filename, $mimes): array
{
	if (!empty($checked['type'])) {
		return $checked;
	}

	$check_filetype = wp_check_filetype($proper_filename, $mimes);
	$ext = $check_filetype['ext'];
	$type = $check_filetype['type'];

	if ($type and str_starts_with($type, 'image/') and $ext !== 'svg') {
		$type = false;
		$ext = false;
	}

	return compact('ext', 'type', 'proper_filename');
}

add_filter('wp_check_filetype_and_ext', 'tw_images_svg_type_filter', 10, 4);


/**
 * Filter the SVG attachment data
 */
function tw_images_svg_js_filter(array $response, WP_Post $attachment, $meta): array
{
	if (!$response['mime'] == 'image/svg+xml') {
		return $response;
	}

	$possible_sizes = apply_filters('image_size_names_choose', [
		'full'      => __('Full Size'),
		'thumbnail' => __('Thumbnail'),
		'medium'    => __('Medium'),
		'large'     => __('Large'),
	]);

	$sizes = [];

	foreach ($possible_sizes as $size => $label) {

		$default_height = 300;
		$default_width = 300;

		$sizes[$size] = [
			'height'      => get_option($size . '_size_w', $default_height),
			'width'       => get_option($size . '_size_h', $default_width),
			'url'         => $response['url'],
			'orientation' => 'portrait',
		];

	}

	$response['sizes'] = $sizes;
	$response['icon'] = $response['url'];

	return $response;
}

add_filter('wp_prepare_attachment_for_js', 'tw_images_svg_js_filter', 10, 3);


/**
 * Generate correct metadata for SVG files
 */
function tw_images_svg_metadata_filter(array $metadata, int $attachment_id): array
{
	$mime = get_post_mime_type($attachment_id);
	$file = get_attached_file($attachment_id);

	if ($mime != 'image/svg+xml' or !file_exists($file)) {
		return $metadata;
	}

	$upload_dir = wp_upload_dir();

	$relative_path = str_replace($upload_dir['basedir'], '', $file);

	$contents = file_get_contents($file);

	$width = 0;
	$height = 0;

	$reg_width = '#<svg[^>]+?width=[\'"]?([0-9.]+)[\'"]?[^>]*?>#i';
	$reg_height = '#<svg[^>]+?height=[\'"]?([0-9.]+)[\'"]?[^>]*?>#i';

	preg_match($reg_width, $contents, $matches);

	if ($matches and !empty($matches[1])) {
		$width = (int) round($matches[1]);
	}

	preg_match($reg_height, $contents, $matches);

	if ($matches and !empty($matches[1])) {
		$height = (int) round($matches[1]);
	}

	if (empty($width) or empty($height)) {

		$reg_viewport = '#<svg[^>]+?viewBox=[\'"]?[0-9.]+\s+[0-9.]+\s+([0-9.]+)\s+([0-9.]+)[\'"]?[^>]*?>#i';

		preg_match($reg_viewport, $contents, $matches);

		if ($matches and !empty($matches[1]) and !empty($matches[2])) {
			$width = (int) round($matches[1]);
			$height = (int) round($matches[2]);
		}

	}

	if (empty($width) or empty($height)) {
		return $metadata;
	}

	return [
		'width'  => $width,
		'height' => $height,
		'file'   => $relative_path
	];
}

add_filter('wp_generate_attachment_metadata', 'tw_images_svg_metadata_filter', 10, 2);


/**
 * Add the registered image sizes to the media editor
 */
function tw_images_label_filter(array $labels): array
{
	$sizes = tw_image_sizes();

	foreach ($sizes as $name => $size) {

		if (!empty($labels[$name])) {
			continue;
		}

		if (!empty($size['label'])) {
			$label = $size['label'];
		} else {
			$label = ucfirst($name);
		}

		$labels[$name] = $label;

	}

	return $labels;
}

add_filter('image_size_names_choose', 'tw_images_label_filter');


/**
 * Compress the image using popular compressing plugins
 *
 * @param string $file     Full path to the image
 * @param string $url      Image URL
 * @param int    $image_id Image ID
 */
function tw_images_compress(string $file, string $url, int $image_id = 0): void
{
	if (!is_readable($file)) {
		return;
	}

	/**
	 * Trigger the WebP conversion using the WebP Converter for Media plugin
	 */
	do_action('webpc_convert_paths', [$file]);

	/**
	 * Integration with the TinyPNG plugin
	 */
	if (class_exists('Tiny_Compress')) {
		try {
			if (defined('TINY_API_KEY')) {
				$api_key = TINY_API_KEY;
			} else {
				$api_key = get_option('tinypng_api_key');
			}

			$compressor = \Tiny_Compress::create($api_key);

			if ($compressor instanceof \Tiny_Compress and $compressor->get_status()->ok) {
				$compressor->compress_file($file, false);
			}
		} catch (\Throwable $exception) {
			tw_logger_error('Failed to compress an image. Error: ' . $exception->getMessage());
		}
	}

	/**
	 * Integration with the WP Smush plugin
	 */
	if (class_exists('WP_Smush')) {
		$smush = \WP_Smush::get_instance()->core()->mod->smush;

		if ($smush instanceof \Smush\Core\Modules\Smush) {
			$smush->do_smushit($file);
		}
	}

	/**
	 * Integration with the EWWW Image Optimizer plugin
	 */
	if (function_exists('ewww_image_optimizer')) {
		ewww_image_optimizer($file, 4, false, false);
	}

	/**
	 * Integration with the Imagify plugin
	 */
	if (class_exists('\Imagify\Optimization\File')) {

		$handler = new \Imagify\Optimization\File($file);

		$result = $handler->optimize([
			'backup'             => false,
			'optimization_level' => 1,
			'keep_exif'          => false,
			'convert'            => '',
			'context'            => 'wp',
		]);

		if ($result instanceof WP_Error) {
			tw_logger_error('Failed to compress an image. Error: ' . $result->get_error_message());
		}

	}

}

add_action('twee_image_compress_event', 'tw_images_compress', 10, 3);


/**
 * Schedule image compression
 *
 * @param string $file     Full path to the image
 * @param string $url      Image URL
 * @param int    $image_id Image ID
 */
function tw_images_schedule_compression(string $file, string $url, int $image_id = 0): void
{
	if (!function_exists('as_schedule_single_action') or !is_readable($file) or filesize($file) > 20 * 1024 * 1024) {
		return;
	}

	$args = [
		'path' => $file,
		'url'  => $url,
		'id'   => $image_id
	];

	$task = 'twee_image_compress_event';

	$time = time() + 600;

	if (function_exists('as_has_scheduled_action') and as_has_scheduled_action($task, $args) === false) {
		as_schedule_single_action($time, $task, $args);
	}
}

add_action('twee_thumb_created', 'tw_images_schedule_compression', 10, 3);