<?php
/**
 * Image Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.3
 */

/**
 * Clear cached thumbnails on image removal
 */
add_action('delete_attachment', 'tw_image_clear');


/**
 * Get a thumbnail in a given size
 *
 * @param int|array|WP_Post $image      A post object, ACF image array, or an attachment ID
 * @param string|array      $size       Size of the image
 * @param string            $before     Code before thumbnail
 * @param string            $after      Code after thumbnail
 * @param array{
 *     domain?: bool,
 *     loading?: bool|string,
 *     decoding?: bool|string,
 *     cards_padding?: int,
 *     cards_width?: int,
 *     cards_gap?: int,
 *     sizes?: array,
 *     srcset?: array,
 *     before?: string,
 *     after?: string,
 *     style?: string,
 *     width?: int,
 *     height?: int,
 *     alt?: string,
 *     class?: string,
 *     link?: string,
 *     link_title?: string,
 *     link_class?: string,
 *     link_target?: string,
 * }                        $attributes Array with attributes
 *
 * @return string
 */
function tw_image($image, string|array $size = 'full', string $before = '', string $after = '', array $attributes = []): string
{
	if ($image instanceof WP_Post) {
		if (empty($attributes['alt'])) {
			$attributes['alt'] = $image->post_title;
		}

		if ($image->post_type === 'attachment') {
			$image = $image->ID;
		} else {
			$image = tw_meta_get('post', $image->ID, '_thumbnail_id');
		}
	} elseif (is_array($image)) {
		if (!empty($image['id'])) {
			$image = $image['id'];
		} elseif (!empty($image['ID'])) {
			$image = $image['ID'];
		}
	}

	$sizes = tw_image_sizes();

	/**
	 * Find the best matching size
	 */
	if ($size == 'auto') {

		if (!empty($attributes['cards_width']) and is_numeric($attributes['cards_width'])) {
			$cards_width = (int) $attributes['cards_width'];
		} else {
			$cards_width = TW_THEME_WIDTH;
		}

		if (!empty($attributes['cards_gap']) and is_numeric($attributes['cards_gap'])) {
			$cards_gap = (int) $attributes['cards_gap'];
		} else {
			$cards_gap = TW_THEME_GAP;
		}

		if (!empty($attributes['cards_padding']) and is_numeric($attributes['cards_padding'])) {
			$cards_padding = (int) $attributes['cards_padding'];
		} else {
			$cards_padding = 0;
		}

		$size = 'medium';

		if (!empty($attributes['sizes']) and is_array($attributes['sizes']) and !empty($attributes['sizes']['dt'])) {
			$value = $attributes['sizes']['dt'];
		} else {
			$value = false;
		}

		$width = 0;

		if ($value) {

			if (is_numeric($value) and $value > 0) {
				if ($value > 100) {
					$width = (int) $value;
				} elseif ($value <= 12) {
					$width = (int) round(($cards_width - $cards_gap * $value - 1) / $value) - $cards_padding;
				} else {
					$width = (int) round($cards_width * $value / 100) - $cards_padding;
				}
			} elseif (strpos($value, 'px') > 0) {
				$width = (int) round(str_replace('px', '', $value));
			} elseif (strpos($value, '%') > 0) {
				$width = (int) round((float) str_replace('%', '', $value) / 100 * $cards_width) - $cards_padding;
			} elseif (strpos($value, 'vw') > 0) {
				$width = (int) round((float) str_replace('vw', '', $value) / 100 * $cards_width) - $cards_padding;
			}

			foreach ($sizes as $key => $value) {
				if (!empty($value['width']) and $value['width'] >= $width) {
					$size = (string) $key;
					break;
				}
			}

		}

	}

	$image_url = tw_image_link($image, $size);

	if (empty($image_url)) {
		return '';
	}

	$link_href = false;
	$link_size = false;

	if (!empty($attributes['link'])) {
		if ($attributes['link'] == 'url' and ($image instanceof WP_Post or is_numeric($image))) {
			$link_href = get_permalink($image);
		} elseif (is_array($attributes['link']) or $attributes['link'] == 'full' or !empty($sizes[$attributes['link']])) {
			$link_size = $attributes['link'];
		} else {
			$link_href = $attributes['link'];
		}
	}

	if (is_numeric($image)) {
		$alt = (string) tw_meta_get('post', $image, '_wp_attachment_image_alt');

		if ($alt) {
			$attributes['alt'] = $alt;
		}
	}

	if (empty($attributes['alt'])) {
		$attributes['alt'] = '';
	}

	if ($link_size and empty($link_href)) {
		$link_href = tw_image_link($image, $link_size);
	}

	if ($link_href) {
		$link_class = '';

		if (!empty($attributes['link_class'])) {
			$link_class = ' class="' . $attributes['link_class'] . '"';
		}

		$link_attributes = '';

		if (!empty($attributes['link_title'])) {
			$link_attributes .= ' title="' . esc_attr($attributes['link_title']) . '"';
		} elseif (!empty($attributes['alt'])) {
			$link_attributes .= ' title="' . esc_attr($attributes['alt']) . '"';
		}

		if (!empty($attributes['link_target'])) {
			$link_attributes .= ' target="' . esc_attr($attributes['link_target']) . '"';
		}

		$before .= '<a href="' . esc_url($link_href) . '"' . $link_class . $link_attributes . '>';
		$after = '</a>' . $after;
	}

	if (!isset($attributes['loading']) or (is_bool($attributes['loading'])) and $attributes['loading']) {
		$attributes['loading'] = 'lazy';
	} else {
		unset($attributes['loading']);
	}

	if (!isset($attributes['decoding']) and !empty($attributes['loading'])) {
		$attributes['decoding'] = 'async';
	}

	if (!empty($attributes['before'])) {
		$before .= $attributes['before'];
	}

	if (!empty($attributes['after'])) {
		$after = $attributes['after'] . $after;
	}

	$empty_dimensions = (empty($attributes['width']) or empty($attributes['height']));

	if (stripos($image_url, '.svg') === false) {
		if (is_numeric($image) and $image > 0 and $empty_dimensions) {
			$data = tw_image_size($size, (int) $image);

			if ($data['width'] > 0 and $data['height'] > 0) {
				$attributes['width'] = round($data['width']);
				$attributes['height'] = round($data['height']);
			}
		}

		if ($empty_dimensions) {
			$dir = tw_image_folders();

			$upload_dir = $dir['basedir'];
			$upload_url = $dir['baseurl'];

			if (str_starts_with($image_url, $upload_url)) {
				$image_path = str_replace($upload_url, $upload_dir, $image_url);

				if (is_file($image_path)) {
					$image_info = getimagesize($image_path);
				} else {
					$image_info = [];
				}

				if (!empty($image_info[0]) and !empty($image_info[1])) {
					$attributes['width'] = round($image_info[0]);
					$attributes['height'] = round($image_info[1]);
				}
			}
		}

		$attributes = tw_image_srcset((int) $image, $attributes);
	} elseif ($image > 0 and $empty_dimensions) {
		$data = tw_image_size($size, (int) $image);

		if ($data['width'] > 0 and $data['height'] > 0) {
			$attributes['width'] = round($data['width']);
			$attributes['height'] = round($data['height']);
		}
	}

	if (empty($attributes['srcset']) or is_array($attributes['srcset'])) {
		unset($attributes['srcset']);
	}

	if (empty($attributes['sizes']) or is_array($attributes['sizes'])) {
		unset($attributes['sizes']);
	}

	$data = [];
	$list = ['loading', 'alt', 'class', 'id', 'width', 'height', 'style', 'srcset', 'sizes', 'decoding', 'fetchpriority'];

	foreach ($attributes as $key => $attribute) {
		if (in_array($key, $list) or str_starts_with($key, 'data')) {
			$data[] = $key . '="' . esc_attr($attribute) . '"';
		}
	}

	$image = $before . '<img src="' . $image_url . '" ' . implode(' ', $data) . ' />' . $after;

	if (empty($attributes['domain'])) {
		$image = str_replace(TW_HOME . '/' . TW_FOLDER, '/', $image);
	}

	return $image;
}


/**
 * Get a thumbnail link
 *
 * @param int|array|string|WP_Post $image WP_Post object, ACF image array or an attachment ID
 * @param string                   $size  Size of the thumbnail
 *
 * @return string
 */
function tw_image_link($image, $size = 'full'): string
{
	$dir = tw_image_folders();

	$upload_dir = $dir['basedir'];
	$upload_url = $dir['baseurl'];

	$thumb_url = '';

	if ($image instanceof WP_Post) {
		if ($image->post_type === 'attachment') {
			$image = $image->ID;
		} else {
			$image = (int) tw_meta_get('post', $image->ID, '_thumbnail_id');
		}
	}

	if (is_array($image)) {

		if (!empty($image['sizes']) and !empty($image['ID']) and is_string($size) and !empty($image['sizes'][$size])) {
			return $image['sizes'][$size];
		}

		if (!empty($image['ID'])) {
			$image = (int) $image['ID'];
		} elseif (!empty($image['id'])) {
			$image = (int) $image['id'];
		} else {
			return '';
		}

	}

	if (empty($image)) {
		return $thumb_url;
	}

	if (is_numeric($image)) {

		$image = (int) $image;

		$file = tw_meta_get('post', $image, '_wp_attached_file');

		if (empty($file)) {
			return apply_filters('wp_get_attachment_url', $thumb_url, $image);
		}

		if (str_starts_with($file, $upload_dir)) {
			$image_url = str_replace($upload_dir, $upload_url, $file);
		} elseif (str_starts_with($file, 'http') or str_starts_with($file, '//')) {
			$image_url = $file;
		} else {
			$image_url = $upload_url . '/' . $file;
		}

		if ($size === 'full' or stripos($image_url, '.svg') > 0) {
			return apply_filters('wp_get_attachment_url', $image_url, $image);
		}

		$meta = (array) tw_meta_get('post', $image, '_wp_attachment_metadata');

		if (!empty($meta['width']) and !empty($meta['height'])) {

			$data = tw_image_size($size, 0);

			/**
			 * WordPress does not create a thumbnail if an image is smaller than required
			 */
			if ($meta['width'] < $data['width'] or $meta['height'] < $data['height']) {
				return apply_filters('wp_get_attachment_url', $image_url, $image);
			}

			/**
			 * Try to find an existing thumbnail with the same dimensions
			 */
			if (!empty($meta['sizes']) and is_array($meta['sizes'])) {
				foreach ($meta['sizes'] as $value) {
					if (isset($value['width']) and ($value['width'] == $data['width']) and isset($value['height']) and $value['height'] == $data['height'] and !empty($value['file'])) {
						$image_url = str_replace(wp_basename($image_url), $value['file'], $image_url);

						return apply_filters('wp_get_attachment_url', $image_url, $image);
					}
				}
			}

		}

		if (is_string($size)) {
			if (!empty($meta['sizes'][$size]) and !empty($meta['sizes'][$size]['file'])) {
				$thumb_url = path_join(dirname($image_url), $meta['sizes'][$size]['file']);
			} else {
				$thumb_url = tw_image_resize($image_url, $size, $image);
			}
		} elseif (is_array($size)) {
			$thumb_url = tw_image_resize($image_url, $size, $image);
		} else {
			$thumb_url = $image_url;
		}

	} else {

		$image = (string) $image;

		if (str_starts_with($image, $upload_dir)) {
			$image = str_replace($upload_dir, $upload_url, $image);
		}

		if (str_starts_with($image, $upload_url)) {
			$thumb_url = tw_image_resize($image, $size, 0);
		} elseif (str_starts_with($image, 'http') or str_starts_with($image, '//')) {
			$thumb_url = tw_image_resize($image, $size, 0);
		} else {
			$path = 'assets/images/' . $image;

			if (file_exists(TW_ROOT . $path)) {
				$thumb_url = tw_image_resize(TW_URL . $path, $size, 0);
			}
		}

		$image = 0;

	}

	return apply_filters('wp_get_attachment_url', $thumb_url, $image);
}


/**
 * Get the thumbnail as a background image or a mask
 *
 * @param int|array|string|WP_Post $image    A post object, ACF image or an attachment ID
 * @param string|array             $size     Size of the image
 * @param string                   $property Return as a mask
 * @param bool                     $style    Include a style attribute
 * @param bool                     $domain   Include a site domain
 *
 * @return string
 */
function tw_image_attribute($image, $size = 'full', string $property = '--mask-image', bool $style = true, $domain = false): string
{
	$link = tw_image_link($image, $size);

	if (empty($link)) {
		return '';
	}

	if (empty($domain)) {
		$link = str_replace(TW_HOME . '/' . TW_FOLDER, '/', $link);
	}

	if ($property) {
		$attribute = $property . ': url(' . esc_attr($link) . ');';
	} else {
		$attribute = 'background-image: url(' . esc_attr($link) . ');';
	}

	if ($style) {
		$attribute = ' style="' . $attribute . '"';
	}

	return $attribute;
}


/**
 * Fill the sizes and srcset attributes
 *
 * @param int   $image
 * @param array $attributes
 *
 * @return array
 */
function tw_image_srcset(int $image, array $attributes): array
{
	if (empty($image) or empty($attributes) or empty($attributes['sizes']) or !is_array($attributes['sizes'])) {
		return $attributes;
	}

	$sizes = tw_image_sizes();

	if (!empty($attributes['cards_width']) and is_numeric($attributes['cards_width'])) {
		$cards_width = (int) $attributes['cards_width'];
	} else {
		$cards_width = TW_THEME_WIDTH;
	}

	if (!empty($attributes['cards_padding']) and is_numeric($attributes['cards_padding'])) {
		$cards_padding = (int) $attributes['cards_padding'];
	} else {
		$cards_padding = 0;
	}

	if (!empty($attributes['cards_gap']) and is_numeric($attributes['cards_gap'])) {
		$cards_gap = (int) $attributes['cards_gap'];
	} else {
		$cards_gap = TW_THEME_GAP;
	}

	$breakpoints = [
		'ps' => 420,
		'pl' => 480,
		'ts' => 640,
		'tl' => 768,
		'ds' => 1024,
		'dl' => TW_THEME_WIDTH - 1,
		'dt' => TW_THEME_WIDTH
	];

	if (empty($attributes['sizes']['dl'])) {
		$keys = ['dt', 'ds', 'tl', 'ts', 'pl', 'ps'];

		foreach ($keys as $key) {
			if (!empty($attributes['sizes'][$key])) {
				$attributes['sizes']['dl'] = $attributes['sizes'][$key];
				break;
			}
		}
	}

	if (empty($attributes['sizes']['dt']) and !empty($attributes['sizes']['dl'])) {
		$attributes['sizes']['dt'] = $attributes['sizes']['dl'];
	}

	$width_list = [];
	$media_list = [];

	foreach ($breakpoints as $breakpoint => $screen_width) {
		if (empty($attributes['sizes'][$breakpoint])) {
			continue;
		}

		$value = $attributes['sizes'][$breakpoint];

		if (is_numeric($value) and $value > 0) {
			if ($value > 100) {
				$width_list[$screen_width] = (int) $value;
				$media_list[$screen_width] = $value . 'px';
			} elseif ($value <= 12) {
				if ($breakpoint == 'dt') {
					$width_list[$screen_width] = (int) round(($cards_width - $cards_gap * ($value - 1)) / $value) - $cards_padding;
					$media_list[$screen_width] = $width_list[$screen_width] . 'px';
				} else {
					$width_list[$screen_width] = (int) round(($screen_width - $cards_gap * ($value - 1)) / $value) - $cards_padding;
					$media_list[$screen_width] = (int) round(100 / $value, 2) . 'vw';
				}
			} elseif ($breakpoint == 'dt') {
				$width_list[$screen_width] = (int) round($cards_width * $value / 100) - $cards_padding;
				$media_list[$screen_width] = $width_list[$screen_width] . 'px';
			} else {
				$width_list[$screen_width] = (int) round(((float) $value / 100) * $screen_width);
				$media_list[$screen_width] = $value . 'vw';
			}
		} elseif (strpos($value, 'px') > 0) {
			$width_list[$screen_width] = (int) round(str_replace('px', '', $value));
			$media_list[$screen_width] = $value;
		} elseif (strpos($value, '%') > 0) {
			$width_list[$screen_width] = (int) round((float) str_replace('%', '', $value) / 100 * $cards_width) - $cards_padding;
			$media_list[$screen_width] = $value;
		} elseif (strpos($value, 'vw') > 0) {
			$width_list[$screen_width] = (int) round((float) str_replace('vw', '', $value) / 100 * $screen_width) - $cards_padding;
			$media_list[$screen_width] = $value;
		}
	}

	if (empty($attributes['srcset']) or !is_array($attributes['srcset'])) {
		$srcset = ['thumbnail', 'medium', 'large', 'full'];

		if ($width_list) {
			foreach ($width_list as $image_width) {
				foreach ($sizes as $key => $array) {
					if (!empty($array['width']) and $array['width'] >= $image_width) {
						$srcset[] = $key;
						break;
					}
				}
			}
		}

		$attributes['srcset'] = array_unique($srcset);
	}

	$queries = [];

	$last_width = '';
	$last_index = '';

	foreach ($media_list as $screen_width => $media_width) {
		if ($screen_width == TW_THEME_WIDTH) {
			$queries[$screen_width] = '(min-width: ' . $screen_width . 'px) ' . $media_width;
		} else {
			if ($last_width === $media_width and isset($queries[$last_index])) {
				unset($queries[$last_index]);
			}
			$queries[$screen_width] = '(max-width: ' . $screen_width . 'px) ' . $media_width;
		}

		$last_width = $media_width;
		$last_index = $screen_width;
	}

	if (empty($queries)) {
		return $attributes;
	}

	ksort($queries);

	$attributes['sizes'] = implode(', ', $queries);

	if (!empty($attributes['srcset']) and is_array($attributes['srcset'])) {
		$srcset = [];
		$widths = [];

		$attributes['srcset'] = array_unique($attributes['srcset']);

		foreach ($attributes['srcset'] as $src_size) {
			$data = tw_image_size($src_size, $image);

			if ($data['width'] > 0 and !in_array($data['width'], $widths)) {
				$widths[] = $data['width'];
				$srcset[] = tw_image_link($image, $src_size) . ' ' . round($data['width']) . 'w';
			}
		}

		$attributes['srcset'] = implode(', ', $srcset);
	}

	return $attributes;
}


/**
 * Register and return thumbnail sizes
 *
 * @param array|bool|string $sizes
 *
 * @return array
 */
function tw_image_sizes($sizes = false): array
{
	$cache_key = 'tw_image_sizes';
	$system_names = ['thumbnail', 'medium', 'large'];

	if (!empty($sizes) and is_array($sizes)) {

		$result = tw_image_sizes();

		foreach ($sizes as $name => $size) {

			if (empty($name) or !is_string($name) or !is_array($size)) {
				continue;
			}

			$is_system_size = in_array($name, $system_names);

			if (empty($size['hidden']) or $is_system_size) {

				if (!isset($size['crop'])) {
					$size['crop'] = true;
				}

				if (empty($size['width'])) {
					$size['width'] = 0;
				}

				if (empty($size['height'])) {
					$size['height'] = 0;
				}

				if ($is_system_size) {

					if (get_option($name . '_size_w') != $size['width']) {
						update_option($name . '_size_w', $size['width']);
					}

					if (get_option($name . '_size_h') != $size['height']) {
						update_option($name . '_size_h', $size['height']);
					}

					if (isset($size['crop']) and get_option($name . '_crop') != $size['crop']) {
						update_option($name . '_crop', $size['crop']);
					}

					$size['system'] = true;

				} else {

					add_image_size($name, $size['width'], $size['height'], $size['crop']);

				}

				if (!empty($size['thumb'])) {
					set_post_thumbnail_size($size['width'], $size['height'], $size['crop']);
				}

			}

			$result[$name] = $size;

		}

		uasort($result, function($a, $b) {
			return $a['width'] <=> $b['width'];
		});

		tw_app_set($cache_key, $result);

		return $result;
	}

	$result = tw_app_get($cache_key);

	if (is_array($result)) {
		if ($sizes and is_string($sizes) and isset($result[$sizes])) {
			return $result[$sizes];
		}

		return $result;
	}

	$system_sizes = [];

	foreach ($system_names as $size) {
		$system_sizes[$size] = [
			'width'  => (int) get_option($size . '_size_w'),
			'height' => (int) get_option($size . '_size_h'),
			'crop'   => (bool) get_option($size . '_crop')
		];
	}

	$sizes = wp_get_additional_image_sizes();

	if (is_array($sizes)) {
		unset($sizes['1536x1536'], $sizes['2048x2048']);
	}

	$sizes = array_merge($system_sizes, $sizes);

	uasort($sizes, function($a, $b) {
		return $a['width'] <=> $b['width'];
	});

	tw_app_set($cache_key, $sizes);

	return $sizes;
}


/**
 * Get an image size
 *
 * @param string|array $size
 * @param int          $image_id
 *
 * @return array
 */
function tw_image_size($size, int $image_id = 0): array
{
	$sizes = tw_image_sizes();

	$result = [
		'width'  => 0,
		'height' => 0,
		'crop'   => true,
		'aspect' => false,
		'cover'  => true
	];

	if (is_array($size)) {
		$result['width'] = $size[0] ?? 0;
		$result['height'] = $size[1] ?? 0;
		$result['crop'] = $size[2] ?? true;
	} elseif (is_string($size) and isset($sizes[$size]) and $size != 'full') {
		$result['width'] = $sizes[$size]['width'] ?? 0;
		$result['height'] = $sizes[$size]['height'] ?? 0;
		$result['crop'] = $sizes[$size]['crop'] ?? true;
		$result['cover'] = $sizes[$size]['cover'] ?? true;
		$result['aspect'] = $sizes[$size]['aspect'] ?? false;
	}

	if ($image_id < 1) {
		return $result;
	}

	$meta = tw_meta_get('post', $image_id, '_wp_attachment_metadata');

	if (is_array($meta) and !empty($meta['width']) and !empty($meta['height'])) {
		if ($size === 'full' or (!empty($meta['file']) and stripos($meta['file'], '.svg') === strlen($meta['file']) - 4)) {
			$result['width'] = $meta['width'];
			$result['height'] = $meta['height'];
		} elseif (!empty($meta['sizes']) and is_string($size) and !empty($meta['sizes'][$size])) {
			$result['width'] = $meta['sizes'][$size]['width'];
			$result['height'] = $meta['sizes'][$size]['height'];
		} else {
			$size = tw_image_calculate($meta['width'], $meta['height'], $result['width'], $result['height'], $result['crop'], $result['aspect']);
			$result['width'] = $size['width'];
			$result['height'] = $size['height'];
		}
	}

	return $result;
}


/**
 * Create a thumbnail and return a link
 *
 * @param string       $image_url
 * @param array|string $image_size
 * @param int          $image_id
 *
 * @return string
 */
function tw_image_resize(string $image_url, array|string $image_size, $image_id = 0): string
{
	$thumb_url = '';

	if (empty($image_url)) {
		return $thumb_url;
	}

	$position = strrpos($image_url, '/');

	if ($position >= strlen($image_url)) {
		return $thumb_url;
	}

	$thumb_name = strtolower(substr($image_url, $position + 1));

	if (preg_match('#(.*?)\.(gif|jpg|jpeg|png|bmp|webp)$#is', $thumb_name, $matches)) {

		$size = tw_image_size($image_size, (int) $image_id);
		$sizes = tw_image_sizes();

		if (is_array($image_size)) {
			$width = $image_size[0] ?? 0;
			$height = $image_size[1] ?? 0;
			$crop = $image_size[2] ?? true;
		} elseif (is_string($image_size) and !empty($sizes[$image_size]) and $image_size != 'full') {
			$width = $sizes[$image_size]['width'] ?? 0;
			$height = $sizes[$image_size]['height'] ?? 0;
			$crop = $sizes[$image_size]['crop'] ?? true;
		} else {
			$width = $size['width'];
			$height = $size['height'];
			$crop = $size['crop'];
		}

		$width = (int) $width;
		$height = (int) $height;

		if (empty($width) and empty($height)) {
			return $image_url;
		}

		$dir = tw_image_folders();

		$upload_dir = $dir['basedir'];
		$upload_url = $dir['baseurl'];

		if ($image_id > 0) {
			$image_id_string = $image_id . '_';
			$url_hash = '';
		} else {
			$image_id_string = '';
			$url_hash = '_' . hash('crc32', $image_url, false);
		}

		$crop = ($crop and is_array($crop)) ? '_' . implode('_', $crop) : '';
		$name = '/cache/thumbs_' . $width . 'x' . $height . '/' . $image_id_string . $matches[1] . $url_hash . $crop;

		$thumb_mime = 'image/webp';
		$thumb_name = $name . '.webp';

		if (!is_dir($upload_dir . '/cache/')) {
			wp_mkdir_p($upload_dir . '/cache/');
		}

		if (is_readable($upload_dir . $thumb_name)) {
			return $upload_url . $thumb_name;
		}

		if (empty($matches[2]) or $matches[2] !== 'png') {
			$fallback_name = $name . '.jpg';
			$fallback_mime = 'image/jpeg';
		} else {
			$fallback_name = $name . '.' . $matches[2];
			$fallback_mime = 'image/png';
		}

		if (is_readable($upload_dir . $fallback_name)) {
			return (filesize($upload_dir . $fallback_name) < 50) ? $image_url : $upload_url . $fallback_name;
		}

		if (str_starts_with($image_url, $upload_url)) {
			$image_path = str_replace($upload_url, $upload_dir, $image_url);
		} else {
			$image_path = $image_url;
		}

		$image_url = trim($image_url);

		if (str_starts_with($image_path, '/') and !str_starts_with($image_path, '//') and !is_file($image_path)) {
			$image_path = $image_url;
		}

		$editor = wp_get_image_editor($image_path);

		if (!$editor instanceof WP_Image_Editor) {
			return $image_url;
		}

		$image_size = (array) $editor->get_size();

		if (!empty($image_size['width']) and !empty($image_size['height'])) {
			$image_size = tw_image_calculate($image_size['width'], $image_size['height'], $width, $height, $size['crop'], $size['aspect']);
			$width = $image_size['width'];
			$height = $image_size['height'];
		}

		$editor->resize($width, $height, $crop);

		$result = $editor->save($upload_dir . $thumb_name, $editor::supports_mime_type($thumb_mime) ? $thumb_mime : null);

		if (!is_array($result) or !is_readable($result['path']) or empty($result['filesize'])) {
			if (empty($result['filesize'])) {
				unlink($result['path']);
			}

			$editor = wp_get_image_editor($image_path);

			$result = $editor->save($upload_dir . $fallback_name, $fallback_mime);

			if (!is_array($result) or !is_readable($result['path']) or empty($result['filesize'])) {
				return $image_url;
			}
		}

		$position = strpos($result['path'], '/cache/');

		if ($position === false) {
			return $image_url;
		}

		$thumb_name = substr($result['path'], $position);

		do_action('twee_thumb_created', $upload_dir . $thumb_name, $upload_url . $thumb_name, $image_id);

		$thumb_url = $upload_url . $thumb_name;

	} elseif (preg_match('#(.*?)\.(svg)$#is', $thumb_name, $matches)) {

		$thumb_url = $image_url;

	}

	return $thumb_url;
}


/**
 * Calculate a thumbnail size
 *
 * Use the aspect parameter to keep the aspect ratio
 * while cropping small images
 *
 * @param int|float  $image_width
 * @param int|float  $image_height
 * @param int|float  $thumb_width
 * @param int|float  $thumb_height
 * @param bool|array $crop   // Crop the image to the specified size
 * @param bool       $aspect // Maintain aspect ratio if source is smaller (only if !upscale)
 * @param bool       $cover  // Force the image to cover the requested dimensions fully
 *
 * @return array
 */
function tw_image_calculate(int|float $image_width, int|float $image_height, int|float $thumb_width, int|float $thumb_height, $crop = true, bool $aspect = false, bool $cover = true): array
{
	if ($image_width < 1 or $image_height < 1) {
		return [
			'width'  => $thumb_width,
			'height' => $thumb_height
		];
	}

	$image_ratio = $image_width / $image_height;

	if ($thumb_width < 1 or $thumb_height < 1) {
		$thumb_ratio = (float) $image_ratio;
	} else {
		$thumb_ratio = $thumb_width / $thumb_height;
	}

	if ($crop) {
		if (!$cover and $thumb_width > $image_width) {
			$thumb_width = $image_width;

			if ($aspect) {
				$thumb_height = $thumb_width / $thumb_ratio;
			}
		}

		if (!$cover and $thumb_height > $image_height) {
			$thumb_height = $image_height;

			if ($aspect) {
				$thumb_width = $thumb_height * $thumb_ratio;
			}
		}
	} else {
		if ($thumb_height < 1) {
			if ($thumb_width < 1) {
				$thumb_width = $image_width;
			}

			$thumb_height = $thumb_width / $thumb_ratio;
		} elseif ($thumb_width < 1) {
			$thumb_width = $thumb_height * $thumb_ratio;
		}

		// Force the image to cover the requested dimensions fully
		if ($cover) {
			if ($image_ratio < $thumb_ratio) {
				// Image is narrower than target
				$thumb_height = $thumb_width / $image_ratio;
			} else {
				// Image is wider than target
				$thumb_width = $thumb_height * $image_ratio;
			}

		} else {
			if ($image_ratio < $thumb_ratio) {
				$thumb_width = $thumb_width * $image_ratio / $thumb_ratio;
			} else {
				$thumb_height = $thumb_height * $thumb_ratio / $image_ratio;
			}

			if ($image_width < $thumb_width) {
				$thumb_width = $image_width;
				$thumb_height = $thumb_width / $image_ratio;
			}
		}
	}

	return [
		'width'  => $thumb_width,
		'height' => $thumb_height
	];
}


/**
 * Get a video poster image from a YouTube or Vimeo URL
 *
 * @param string $video_url
 * @param bool   $return_url
 *
 * @return string
 */
function tw_image_preview(string $video_url, $return_url = true): string
{
	if (empty($video_url)) {
		return '';
	}

	$dir = tw_image_folders();
	$cache_dir = $dir['basedir'] . '/cache/videos/';
	$cache_url = $dir['baseurl'] . '/cache/videos/';

	$lock_path = '';
	$poster_url = '';
	$poster_path = '';

	if (!is_dir($cache_dir)) {
		wp_mkdir_p($cache_dir);
	}

	if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*?&)?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $video_url, $match)) {

		$youtube_id = $match[1];

		$lock_path = $cache_dir . 'youtube_' . $youtube_id . '.lock';
		$poster_path = $cache_dir . 'youtube_' . $youtube_id . '.jpg';
		$poster_url = $cache_url . 'youtube_' . $youtube_id . '.jpg';

		if (is_readable($poster_path)) {
			return $return_url ? $poster_url : $poster_path;
		} elseif (is_readable($lock_path)) {
			return '';
		}

		$youtube_url = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
		$response = wp_remote_get($youtube_url, ['timeout' => 10]);

		if (is_wp_error($response) or wp_remote_retrieve_response_code($response) !== 200) {
			$youtube_url = "https://img.youtube.com/vi/{$youtube_id}/hqdefault.jpg";
			$response = wp_remote_get($youtube_url, ['timeout' => 10]);
		}

		if (!is_wp_error($response) and wp_remote_retrieve_response_code($response) === 200) {
			$body = wp_remote_retrieve_body($response);

			if ($body) {
				file_put_contents($poster_path, $body);

				return $return_url ? $poster_url : $poster_path;
			}
		}

	} elseif (preg_match('#(?:(?:www\.)?player\.)?(?:www\.)?vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?([0-9]+)#i', $video_url, $match)) {

		$vimeo_id = $match[1];
		$vimeo_url = '';

		$lock_path = $cache_dir . 'vimeo_' . $vimeo_id . '.lock';
		$poster_path = $cache_dir . 'vimeo_' . $vimeo_id . '.jpg';
		$poster_url = $cache_url . 'vimeo_' . $vimeo_id . '.jpg';

		if (is_readable($poster_path)) {
			return $return_url ? $poster_url : $poster_path;
		} elseif (is_readable($lock_path)) {
			return '';
		}

		$response = wp_remote_get("https://vimeo.com/api/v2/video/{$vimeo_id}.json", ['timeout' => 15]);

		if (!is_wp_error($response) and wp_remote_retrieve_response_code($response) === 200) {
			$body = json_decode(wp_remote_retrieve_body($response), true);

			if (!empty($body[0]['thumbnail_large'])) {
				$vimeo_url = $body[0]['thumbnail_large'];
			}
		}

		if ($vimeo_url) {
			$image_response = wp_remote_get($vimeo_url, ['timeout' => 15]);

			if (!is_wp_error($image_response) and wp_remote_retrieve_response_code($image_response) === 200) {
				$image_body = wp_remote_retrieve_body($image_response);

				if ($image_body) {
					file_put_contents($poster_path, $image_body);
				}
			}
		}
	}

	if ($poster_path and is_readable($poster_path)) {
		return $return_url ? $poster_url : $poster_path;
	}

	if ($lock_path) {
		file_put_contents($lock_path, time());
	}

	return '';
}


/**
 * Clear cached thumbnails
 *
 * @param int $image_id
 *
 * @return void
 */
function tw_image_clear($image_id): void
{
	$dir = tw_image_folders();

	$base = $dir['basedir'] . '/cache/';

	if (!is_dir($base)) {
		return;
	}

	$image_id = (int) $image_id;

	$folders = array_diff(scandir($base), ['..', '.', 'logs']);

	foreach ($folders as $folder) {
		if (!str_contains($folder, 'thumbs_') or !(is_dir($base . $folder))) {
			continue;
		}

		$files = scandir($base . $folder);

		foreach ($files as $file) {
			if (str_starts_with($file, $image_id . '_') and is_readable($base . $folder . '/' . $file)) {
				unlink($base . $folder . '/' . $file);
			}
		}
	}
}


/**
 * Get the upload folders
 *
 * @return array
 */
function tw_image_folders(): array
{
	$cache_key = 'tw_image_folders';
	$cache_group = 'image';

	$folders = tw_app_get($cache_key, $cache_group);

	if (is_array($folders)) {
		return $folders;
	}

	$dir = wp_upload_dir();

	$folders = [
		'basedir' => $dir['basedir'] ?? '',
		'baseurl' => $dir['baseurl'] ?? '',
	];

	tw_app_set($cache_key, $folders, $cache_group);

	return $folders;
}