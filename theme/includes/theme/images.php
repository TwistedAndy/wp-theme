<?php
/**
 * Additional filters for image processing
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/**
 * Add the max-width property to the default caption shortcode and fix its width
 */
add_filter('img_caption_shortcode', function($value = false, $attr = [], $content = '') {

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

	$style = 'style="max-width: ' . $atts['width'] . 'px;"';

	return '<div ' . $atts['id'] . $atts['class'] . $style . '>' . do_shortcode($content) . '<p class="wp-caption-text">' . $atts['caption'] . '</p></div>';

}, 20, 3);


/**
 * Remove some image sizes
 */
add_filter('intermediate_image_sizes', function($sizes) {
	return array_diff($sizes, ['medium_large', '1536x1536', '2048x2048']);
}, 20);


/**
 * Add SVG support
 */
add_filter('upload_mimes', function($mimes) {
	$mimes['svg'] = 'image/svg+xml';
	$mimes['svgz'] = 'image/svg+xml';
	return $mimes;
}, 20);

add_filter('wp_check_filetype_and_ext', function($checked, $file, $filename, $mimes) {

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

}, 10, 4);

add_filter('wp_prepare_attachment_for_js', function($response, $attachment, $meta) {

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

}, 10, 3);


/**
 * Generate correct metadata for SVG files
 */
add_filter('wp_generate_attachment_metadata', function($metadata, $attachment_id) {

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
}, 10, 2);


/**
 * Add the registered image sizes to the media editor
 *
 * @param $sizes array
 *
 * @return array
 */
add_filter('image_size_names_choose', function($sizes) {

	$data = tw_image_sizes();

	if (!empty($data['custom'])) {

		foreach ($data['custom'] as $name => $size) {

			if (isset($sizes[$name])) {
				continue;
			}

			if (!empty($size['label'])) {
				$label = $size['label'];
			} else {
				$label = ucfirst($name);
			}

			$sizes[$name] = $label;

		}

	}

	return $sizes;

});


/**
 * Compress the image using popular compressing plugins
 *
 * @param string $file     Full path to the image
 * @param string $url      Image URL
 * @param int    $image_id Image ID
 */
add_action('twee_image_compress', function($file, $url, $image_id = 0) {

	if (!is_readable($file)) {
		return;
	}

	/**
	 * Trigger the WebP conversion using the WebP Converter for Media plugin
	 */
	do_action('webpc_convert_paths', [$file]);


	/**
	 * Check all popular image compressing plugins
	 */
	if (class_exists('WP_Smush')) {

		/* Integration with the Smush plugin */

		$smush = \WP_Smush::get_instance()->core()->mod->smush;

		if ($smush instanceof \Smush\Core\Modules\Smush) {
			$smush->do_smushit($file);
		}

	} elseif (function_exists('ewww_image_optimizer')) {

		/* Integration with the EWWW Image Optimizer and EWWW Image Optimizer Cloud plugins */

		ewww_image_optimizer($file, 4, false, false);

	} elseif (class_exists('Tiny_Compress')) {

		/* Integration with the TinyPNG plugin */

		if (defined('TINY_API_KEY')) {
			$api_key = TINY_API_KEY;
		} else {
			$api_key = get_option('tinypng_api_key');
		}

		$compressor = \Tiny_Compress::create($api_key);

		if ($compressor instanceof \Tiny_Compress and $compressor->get_status()->ok) {

			try {
				$compressor->compress_file($file, false);
			} catch (\Tiny_Exception $exception) {
				tw_logger_error('Failed to compress an image. Error: ' . $exception->getMessage());
			}

		}

	} elseif (class_exists('WRIO_Plugin') and class_exists('WIO_OptimizationTools')) {

		/* Integration with the Webcraftic Robin image optimizer */

		$image_processor = \WIO_OptimizationTools::getImageProcessor();

		$optimization_level = \WRIO_Plugin::app()->getPopulateOption('image_optimization_level', 'normal');

		if ($optimization_level == 'custom') {
			$optimization_level = intval(\WRIO_Plugin::app()->getPopulateOption('image_optimization_level_custom', 100));
		}

		$image_data = $image_processor->process([
			'image_url' => $url,
			'image_path' => $file,
			'quality' => $image_processor->quality($optimization_level),
			'save_exif' => \WRIO_Plugin::app()->getPopulateOption('save_exif_data', false),
			'is_thumb' => false,
		]);

		if ($image_data instanceof WP_Error) {
			tw_logger_error('Failed to compress an image. Error: ' . $image_data->get_error_message());
		}

		if (!is_wp_error($image_data) and empty($image_data['not_need_replace']) and !empty($image_data['optimized_img_url']) and strpos($image_data['optimized_img_url'], 'http') === 0) {

			$temp_file = $file . '.tmp';

			$fp = fopen($temp_file, 'w+');

			if ($fp === false) {
				return;
			}

			$ch = curl_init($image_data['optimized_img_url']);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_exec($ch);

			if (curl_errno($ch)) {
				tw_logger_error('Failed to compress an image. Error: ' . curl_error($ch));
				return;
			}

			$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			curl_close($ch);

			fclose($fp);

			if ($status == 200) {

				$type = mime_content_type($temp_file);

				$types = [
					'image/png' => 'png',
					'image/bmp' => 'bmp',
					'image/jpeg' => 'jpg',
					'image/pjpeg' => 'jpg',
					'image/gif' => 'gif',
					'image/svg' => 'svg',
					'image/svg+xml' => 'svg',
				];

				if (!empty($types[$type])) {
					copy($temp_file, $file);
				}

				unlink($temp_file);

			}

		}

	} elseif (class_exists('\Imagify\Optimization\File')) {

		$file = new \Imagify\Optimization\File($file);

		$result = $file->optimize([
			'backup' => false,
			'optimization_level' => 1,
			'keep_exif' => false,
			'convert' => '',
			'context' => 'wp',
		]);

		if ($result instanceof WP_Error) {
			tw_logger_error('Failed to compress an image. Error: ' . $result->get_error_message());
		}

	}

}, 10, 3);


/**
 * Schedule image compression
 *
 * @param string $file     Full path to the image
 * @param string $url      Image URL
 * @param int    $image_id Image ID
 */
add_action('twee_thumb_created', function($file, $url, $image_id) {

	if (!function_exists('as_schedule_single_action')) {
		return;
	}

	$args = [
		'path' => $file,
		'url' => $url,
		'id' => $image_id
	];

	$task = 'twee_image_compress';

	$time = time() + 600;

	if (as_has_scheduled_action($task, $args) === false) {
		as_schedule_single_action($time, $task, $args);
	}

}, 10, 3);

