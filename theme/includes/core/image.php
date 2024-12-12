<?php
/**
 * Image Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/**
 * Clear cached thumbnails on image removal
 */
add_action('delete_attachment', 'tw_image_clear');


/**
 * Get the thumbnail with given size
 *
 * @param int|array|WP_Post $image      A post object, ACF image array, or an attachment ID
 * @param string|array      $size       Size of the image
 * @param string            $before     Code before thumbnail
 * @param string            $after      Code after thumbnail
 * @param array             $attributes Array with attributes
 *
 * @return string
 */
function tw_image($image, $size = 'full', $before = '', $after = '', $attributes = []) {

	if (!is_array($attributes)) {
		$attributes = [];
	}

	$base_url = !empty($attributes['base_url']);

	$thumb = tw_image_link($image, $size, $base_url);

	if (empty($thumb)) {
		return '';
	}

	$link_href = false;
	$link_image_size = false;

	if (!empty($attributes['link'])) {

		if ($attributes['link'] == 'url' and $image instanceof WP_Post) {

			$link_href = get_permalink($image);

		} else {

			$sizes = tw_image_sizes();

			$sizes = $sizes['registered'];

			if (is_array($attributes['link']) or $attributes['link'] == 'full' or !empty($sizes[$attributes['link']])) {
				$link_image_size = $attributes['link'];
			} else {
				$link_href = $attributes['link'];
			}

		}

		if ($link_href and empty($base_url)) {
			$link_href = str_replace(TW_HOME, '', $link_href);
		}

	}

	if ($image instanceof WP_Post) {

		if (empty($attributes['alt'])) {
			$attributes['alt'] = $image->post_title;
		}

		if ($image->post_type === 'attachment') {
			$image = $image->ID;
		} else {
			$image = get_post_meta($image->ID, '_thumbnail_id', true);
		}

	} elseif (is_array($image) and !empty($image['id'])) {

		$image = $image['id'];

	}

	if (is_numeric($image)) {

		$alt = (string) get_post_meta($image, '_wp_attachment_image_alt', true);

		if ($alt) {
			$attributes['alt'] = $alt;
		}

	}

	if (empty($attributes['alt'])) {
		$attributes['alt'] = '';
	}

	if ($link_image_size and empty($link_href)) {
		$link_href = tw_image_link($image, $link_image_size, $base_url);
	}

	if ($link_href) {

		$link_class = '';

		if (!empty($attributes['link_class'])) {
			$link_class = ' class="' . $attributes['link_class'] . '"';
		}

		$before = $before . '<a href="' . esc_url($link_href) . '"' . $link_class . ' title="' . esc_attr($attributes['alt']) . '">';
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
		$before = $before . $attributes['before'];
	}

	if (!empty($attributes['after'])) {
		$after = $attributes['after'] . $after;
	}

	if (empty($attributes['width']) or empty($attributes['height'])) {

		$data = tw_image_size($size, $image);

		if ($data['width'] > 0 and $data['height'] > 0) {
			$attributes['width'] = round($data['width']);
			$attributes['height'] = round($data['height']);
		}

	}

	if (!empty($attributes['srcset']) and is_array($attributes['srcset'])) {

		$srcset = [];

		$attributes['srcset'][] = $size;

		$attributes['srcset'] = array_unique($attributes['srcset']);

		foreach ($attributes['srcset'] as $src_size) {

			$data = tw_image_size($src_size, $image);

			if ($data['width'] > 0) {
				$srcset[] = tw_image_link($image, $src_size, $base_url) . ' ' . round($data['width']) . 'w';
			}

		}

		$attributes['srcset'] = implode(', ', $srcset);

		if (!empty($attributes['sizes']) and is_array($attributes['sizes'])) {

			$breakpoints = [
				'ps' => 420,
				'pl' => 480,
				'ts' => 640,
				'tl' => 768,
				'ds' => 1024,
				'dl' => 1360,
				'dt' => 3840
			];

			$queries = [];

			foreach ($attributes['sizes'] as $breakpoint => $value) {

				$screen = 0;
				$width = '';

				if (strpos($value, 'px') > 0) {
					$width = $value;
				} elseif (is_numeric($value)) {
					if ($value > 100) {
						$width = $value . 'px';
					} else {
						$width = $value . 'vw';
					}
				} elseif (is_string($value)) {
					$width = $value;
				}

				if (empty($width)) {
					continue;
				}

				if (is_numeric($breakpoint)) {
					$screen = $breakpoint;
				} elseif (!empty($breakpoints[$breakpoint]) and is_numeric($breakpoints[$breakpoint])) {
					$screen = $breakpoints[$breakpoint];
				}

				if ($screen < 320) {
					continue;
				}

				$queries[$screen] = $width;

			}

			if ($queries) {

				$sizes = [];

				ksort($queries);

				foreach ($queries as $screen => $width) {
					$sizes[] = '(max-width: ' . $screen . 'px) ' . $width;
				}

				$attributes['sizes'] = implode(', ', $sizes);

			}

		}

	}

	if (empty($attributes['srcset'])) {
		unset($attributes['srcset']);
	}

	if (empty($attributes['sizes'])) {
		unset($attributes['sizes']);
	}

	$data = [];
	$list = ['loading', 'alt', 'class', 'id', 'width', 'height', 'style', 'srcset', 'sizes', 'decoding', 'fetchpriority'];

	foreach ($attributes as $key => $attribute) {
		if (in_array($key, $list) or strpos($key, 'data') === 0) {
			$data[] = $key . '="' . esc_attr($attribute) . '"';
		}
	}

	if ($data) {
		$data = ' ' . implode(' ', $data);
	} else {
		$data = '';
	}

	return $before . '<img src="' . $thumb . '"' . $data . ' />' . $after;

}


/**
 * Get a thumbnail link
 *
 * @param int|array|WP_Post $image    WP_Post object, ACF image array or an attachment ID
 * @param string|array      $size     Size of the thumbnail
 * @param bool              $base_url Include the base URL to the image
 *
 * @return string
 */
function tw_image_link($image, $size = 'full', $base_url = false) {

	$dir = wp_upload_dir();

	$upload_dir = $dir['basedir'];
	$upload_url = $dir['baseurl'];

	$thumb_url = '';

	if ($image instanceof WP_Post) {
		if ($image->post_type === 'attachment') {
			$image = $image->ID;
		} else {
			$image = (int) get_post_meta($image->ID, '_thumbnail_id', true);
		}
	}

	if (is_array($image) and !empty($image['sizes']) and !empty($image['ID'])) {

		if (is_string($size) and !empty($image['sizes'][$size])) {
			$thumb_url = $image['sizes'][$size];
		} else {
			$image = (int) $image['ID'];
		}

	}

	if (empty($image)) {
		return $thumb_url;
	}

	if (is_numeric($image)) {

		$file = get_post_meta($image, '_wp_attached_file', true);

		if ($file) {

			if (0 === strpos($file, $upload_dir)) {
				$image_url = str_replace($upload_dir, $upload_url, $file);
			} elseif (strpos($file, 'http') === 0 or strpos($file, '//') === 0) {
				$image_url = $file;
			} else {
				$image_url = $upload_url . '/' . $file;
			}

			if (empty($base_url)) {
				$image_url = str_replace(TW_HOME, '', $image_url);
			}

			if ($size === 'full' or strpos($image_url, '.svg') > 0) {
				return apply_filters('wp_get_attachment_url', $image_url, $image);
			}

			$meta = get_post_meta($image, '_wp_attachment_metadata', true);

			if (!is_array($meta)) {
				$meta = [];
			}

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
					$thumb_url = tw_image_resize($image_url, $size, $image, $base_url);
				}

			} elseif (is_array($size)) {

				$thumb_url = tw_image_resize($image_url, $size, $image, $base_url);

			} else {

				$thumb_url = $image_url;

			}

		}

	} elseif (is_string($image)) {

		if (strpos($image, 'http') === 0 or strpos($image, '//') === 0) {

			$thumb_url = tw_image_resize($image, $size, 0, $base_url);

		} else {

			$path = 'assets/images/' . $image;

			if (file_exists(TW_ROOT . $path)) {
				$thumb_url = tw_image_resize(TW_URL . $path, $size, 0, $base_url);
			}

		}

		$image = 0;

	}

	return apply_filters('wp_get_attachment_url', $thumb_url, $image);

}


/**
 * Get the thumbnail as a background image or a mask
 *
 * @param int|array|WP_Post $image    A post object, ACF image or an attachment ID
 * @param string|array      $size     Size of the image
 * @param string            $property Return as a mask
 * @param bool              $style    Include a style attribute
 * @param bool              $base_url Include a site base URL
 *
 * @return string
 */
function tw_image_attribute($image, $size = 'full', $property = '--mask-image', $style = true, $base_url = false) {

	$link = tw_image_link($image, $size, $base_url);

	if (empty($link)) {
		return '';
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
 * Get an image size
 *
 * @param string|array $size
 * @param int          $image_id
 *
 * @return array
 */
function tw_image_size($size, $image_id = 0) {

	$sizes = tw_image_sizes();

	$sizes = array_merge($sizes['registered'], $sizes['hidden']);

	$result = [
		'width' => 0,
		'height' => 0,
		'crop' => true,
		'aspect' => false
	];

	if (is_array($size)) {
		$result['width'] = $size[0] ?? 0;
		$result['height'] = $size[1] ?? 0;
		$result['crop'] = $size[2] ?? true;
		$result['keep'] = $size[3] ?? true;
	} elseif (is_string($size) and isset($sizes[$size]) and $size != 'full') {
		$result['width'] = $sizes[$size]['width'] ?? 0;
		$result['height'] = $sizes[$size]['height'] ?? 0;
		$result['crop'] = $sizes[$size]['crop'] ?? true;
		$result['aspect'] = $sizes[$size]['aspect'] ?? false;
	}

	if ($image_id > 0) {

		$meta = get_post_meta($image_id, '_wp_attachment_metadata', true);

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

	}

	return $result;

}


/**
 * Create a thumbnail and return a link
 *
 * @param string       $image_url
 * @param array|string $size
 * @param int          $image_id
 * @param bool         $base_url
 *
 * @return string
 */
function tw_image_resize($image_url, $size, $image_id = 0, $base_url = false) {

	$thumb_url = '';

	if (empty($image_url)) {
		return $thumb_url;
	}

	$position = strrpos($image_url, '/');

	if ($position < strlen($image_url)) {

		$filename = strtolower(substr($image_url, $position + 1));

		if (preg_match('#(.*?)\.(gif|jpg|jpeg|png|bmp|webp)$#is', $filename, $matches)) {

			$data = tw_image_size($size, $image_id);

			$sizes = tw_image_sizes();
			$sizes = array_merge($sizes['registered'], $sizes['hidden']);

			if (is_array($size)) {
				$width = $size[0] ?? 0;
				$height = $size[1] ?? 0;
				$crop = $size[2] ?? true;
			} elseif (is_string($size) and !empty($sizes[$size]) and $size != 'full') {
				$width = $sizes[$size]['width'] ?? 0;
				$height = $sizes[$size]['height'] ?? 0;
				$crop = $sizes[$size]['crop'] ?? true;
			} else {
				$width = $data['width'];
				$height = $data['height'];
				$crop = $data['crop'];
			}

			$width = (int) $width;
			$height = (int) $height;

			$thumb_url = $image_url;

			if ($width > 0 or $height > 0) {

				$dir = wp_upload_dir();

				$upload_dir = $dir['basedir'];
				$upload_url = $dir['baseurl'];

				if ($image_id > 0) {
					$image_id_string = $image_id . '_';
					$url_hash = '';
				} else {
					$image_id_string = '';
					$url_hash = '_' . hash('crc32', $image_url, false);
				}

				if (is_array($crop)) {
					$crop_hash = '_' . implode('_', $crop);
				} else {
					$crop_hash = '';
				}

				$filename = '/cache/thumbs_' . $width . 'x' . $height . '/' . $image_id_string . $matches[1] . $url_hash . $crop_hash . '.webp';

				if (!is_dir($upload_dir . '/cache/')) {
					mkdir($upload_dir . '/cache/', 0755, true);
				}

				if (!is_file($upload_dir . $filename)) {
					$filename = str_replace('.webp', '.' . $matches[2], $filename);
				}

				if (!is_file($upload_dir . $filename)) {

					if (strpos($image_url, TW_HOME) === 0) {
						$image_path = str_replace(TW_HOME, '', $image_url);
					} else {
						$image_path = $image_url;
					}

					if (strpos($image_path, '/') === 0 and strpos($image_path, '//') !== 0) {

						$image_path = untrailingslashit(ABSPATH) . $image_path;

						if (!is_file($image_path)) {
							$image_path = $image_url;
						}

					}

					$editor = wp_get_image_editor($image_path);

					if (!is_wp_error($editor)) {

						$size = $editor->get_size();

						if (!empty($size['width']) and !empty($size['height'])) {
							$size = tw_image_calculate($size['width'], $size['height'], $width, $height, $data['crop'], $data['aspect']);
							$width = $size['width'];
							$height = $size['height'];
						}

						$editor->resize($width, $height, $crop);

						$mime_type = 'image/webp';

						if (!$editor->supports_mime_type($mime_type)) {
							$mime_type = null;
						}

						$result = $editor->save($upload_dir . $filename, $mime_type);

						if (is_array($result) and is_readable($result['path'])) {

							$position = strpos($result['path'], '/cache/');

							if ($position > 0) {
								$filename = substr($result['path'], $position);
							} else {
								return $image_url;
							}

						} else {

							return $image_url;

						}

						do_action('twee_thumb_created', $upload_dir . $filename, $upload_url . $filename, $image_id);

					} else {

						return $image_url;

					}

				}

				$thumb_url = $upload_url . $filename;

			}

		} elseif (preg_match('#(.*?)\.(svg)$#is', $filename, $matches)) {

			$thumb_url = $image_url;

		}

	}

	if (empty($base_url)) {
		$thumb_url = str_replace(TW_HOME, '', $thumb_url);
	}

	return $thumb_url;

}


/**
 * Calculate a thumbnail size
 *
 * Use the aspect parameter to keep the aspect ratio
 * while cropping small images
 *
 * @param int        $image_width
 * @param int        $image_height
 * @param int        $thumb_width
 * @param int        $thumb_height
 * @param bool|array $crop
 * @param bool       $aspect
 *
 * @return array
 */
function tw_image_calculate($image_width, $image_height, $thumb_width, $thumb_height, $crop = true, $aspect = false) {

	$image_ratio = $image_width / $image_height;

	if (empty($thumb_width) or empty($thumb_height)) {
		$thumb_ratio = $image_ratio;
	} else {
		$thumb_ratio = $thumb_width / $thumb_height;
	}

	if ($crop) {

		if ($thumb_width > $image_width) {

			$thumb_width = $image_width;

			if ($aspect) {
				$thumb_height = $thumb_width / $thumb_ratio;
			}

		}

		if ($thumb_height > $image_height) {

			$thumb_height = $image_height;

			if ($aspect) {
				$thumb_width = $thumb_height * $thumb_ratio;
			}

		}

	} else {

		if (empty($thumb_height)) {

			if (empty($thumb_width) or !is_numeric($thumb_width)) {
				$thumb_width = $image_width;
			}

			$thumb_height = $thumb_width / $thumb_ratio;

		}

		if (empty($thumb_width)) {

			if (empty($thumb_height) or !is_numeric($thumb_height)) {
				$thumb_height = $image_height;
			}

			$thumb_width = $thumb_height * $thumb_ratio;

		}

		if ($image_ratio < $thumb_ratio) {
			$thumb_width = $thumb_width * $image_ratio / $thumb_ratio;
		} else {
			$thumb_height = $thumb_height * $thumb_ratio / $image_ratio;
		}

		if ($image_width < $thumb_width) {
			$thumb_width = $image_width;
			$thumb_height = $thumb_width / $thumb_ratio;
		}

		if ($image_height < $thumb_height) {
			$thumb_height = $image_height;
			$thumb_width = $thumb_height * $thumb_ratio;
		}

	}

	return [
		'width' => $thumb_width,
		'height' => $thumb_height
	];

}


/**
 * Register and return thumbnail sizes
 *
 * @param array $sizes
 *
 * @return void
 */
function tw_image_sizes($sizes = []) {

	$cache_key = 'tw_image_sizes';

	$data = tw_app_get($cache_key);

	$default_sizes = ['thumbnail', 'medium', 'medium_large', 'large'];

	if (!is_array($data)) {

		$data = [];

		foreach ($default_sizes as $size) {

			$data[$size] = [
				'width' => get_option($size . '_size_w'),
				'height' => get_option($size . '_size_h'),
				'crop' => get_option($size . '_crop')
			];

		}

		$data = [
			'hidden' => [],
			'custom' => [],
			'registered' => array_merge($data, wp_get_additional_image_sizes())
		];

	}

	if (!empty($sizes) and is_array($sizes)) {

		foreach ($sizes as $name => $size) {

			if (empty($name) or !is_string($name) or !is_array($size)) {
				continue;
			}

			$is_default = in_array($name, $default_sizes);

			if (empty($size['hidden']) or $is_default) {

				if (!isset($size['crop'])) {
					$size['crop'] = true;
				}

				if (!isset($size['width'])) {
					$size['width'] = 0;
				}

				if (!isset($size['height'])) {
					$size['height'] = 0;
				}

				if ($is_default) {

					if (get_option($name . '_size_w') != $size['width']) {
						update_option($name . '_size_w', $size['width']);
					}

					if (get_option($name . '_size_h') != $size['height']) {
						update_option($name . '_size_h', $size['height']);
					}

					if (isset($size['crop']) and get_option($name . '_crop') != $size['crop']) {
						update_option($name . '_crop', $size['crop']);
					}

					$data['registered'][$name] = $size;

				} else {

					add_image_size($name, $size['width'], $size['height'], $size['crop']);

				}

				if (isset($size['thumb']) and $size['thumb']) {

					set_post_thumbnail_size($size['width'], $size['height'], $size['crop']);

				}

			} else {

				$data['hidden'][$name] = $size;

			}

			$data['custom'][$name] = $size;

		}

		tw_app_set($cache_key, $data);

	}

	return $data;

}


/**
 * Clear cached thumbnails
 *
 * @param int $image_id
 *
 * @return void
 */
function tw_image_clear($image_id) {

	$dir = wp_upload_dir();

	$base = $dir['basedir'] . '/cache/';

	if (!is_dir($base)) {
		return;
	}

	$folders = array_diff(scandir($base), ['..', '.', 'logs']);

	foreach ($folders as $folder) {

		if (strpos($folder, 'thumbs_') === false or !(is_dir($base . $folder))) {
			continue;
		}

		$files = scandir($base . $folder);

		foreach ($files as $file) {
			if (strpos($file, $image_id . '_') === 0 and is_readable($base . $folder . '/' . $file)) {
				unlink($base . $folder . '/' . $file);
			}
		}

	}

}