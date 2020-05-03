<?php
/**
 * Thumbnail processing library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.1
 */


/**
 * Find the first image and return its link
 *
 * @param string $text
 *
 * @return string
 */

function tw_find_image($text) {

	preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $text, $matches);

	if (isset($matches[1][0]) and $matches[1][0]) {

		$image = $matches[1][0];

		if (strpos($image, '/') === 0) {
			$image = get_site_url() . $image;
		} elseif (strpos($image, 'wp-content') === 0) {
			$image = get_site_url() . '/' . $image;
		}

		return esc_url($image);

	} else {

		return '';

	}

}


/**
 * Get the link to an image with specified size
 *
 * @param string       $image_url Image URL
 * @param array|string $size      Size of the image
 * @param int          $image_id  Image ID
 *
 * @return string
 */

function tw_create_thumb($image_url, $size, $image_id = 0) {

	$thumb_url = '';

	if (empty($image_url)) {
		return $thumb_url;
	}

	$position = mb_strrpos($image_url, '/');

	if ($position < mb_strlen($image_url)) {

		$filename = mb_strtolower(mb_substr($image_url, $position + 1));

		if (preg_match('#(.*?)\.(gif|jpg|jpeg|png|bmp)$#is', $filename, $matches)) {

			$sizes = tw_get_thumb_sizes(true);

			$width = 0;
			$height = 0;
			$crop = true;
			$thumb_url = $image_url;

			if (is_array($size)) {
				$width = (isset($size[0])) ? intval($size[0]) : 0;
				$height = (isset($size[1])) ? intval($size[1]) : 0;
				$crop = (isset($size[2])) ? $size[2] : true;
			} elseif (is_string($size) and isset($sizes[$size]) and $size != 'full') {
				$width = (isset($sizes[$size]['width'])) ? intval($sizes[$size]['width']) : 0;
				$height = (isset($sizes[$size]['height'])) ? intval($sizes[$size]['height']) : 0;
				$crop = (isset($sizes[$size]['crop'])) ? $sizes[$size]['crop'] : true;
			}

			if ($width > 0 or $height > 0) {

				if ($image_id > 0) {
					$image_id = $image_id . '_';
					$hash = '';
				} else {
					$image_id = '';
					$hash = '_' . hash('crc32', $image_url, false);
				}

				$filename = '/cache/' . $image_id . $matches[1] . '_' . $width . 'x' . $height . $hash . '.' . $matches[2];

				$upload_dir = wp_upload_dir();

				if (!empty($upload_dir['basedir']) and !empty($upload_dir['baseurl'])) {
					$directory = $upload_dir['basedir'];
					$directory_uri = $upload_dir['baseurl'];
				} else {
					$directory = get_template_directory();
					$directory_uri = get_template_directory_uri();
				}

				if (!is_file($directory . $filename)) {

					$site_url = get_option('siteurl');

					$image_path = $image_url;

					if (strpos($image_url, $site_url) === 0) {

						$image_path = str_replace(trailingslashit($site_url), ABSPATH, $image_url);

						if (!is_file($image_path)) {
							$image_path = $image_url;
						}

					}

					$editor = wp_get_image_editor($image_path);

					if (!is_wp_error($editor)) {

						$image_size = $editor->get_size();

						if (!empty($image_size['width']) and !empty($image_size['height'])) {

							$image_width = $image_size['width'];
							$image_height = $image_size['height'];

							if (empty($crop) or empty($width) or empty($height)) {
								$ratio = $image_width / $image_height;
							} else {
								$ratio = $width / $height;
							}

							if ($width > 0 and $width > $image_width) {
								$width = $image_width;
								$height = round($width / $ratio);
							}

							if ($height > 0 and $height > $image_height) {
								$height = $image_height;
								$width = round($height / $ratio);
							}

						}

						$editor->resize($width, $height, $crop);

						$editor->save($directory . $filename);

					} else {

						return $image_url;

					}

				}

				$thumb_url = $directory_uri . $filename;

			}

		} elseif (preg_match('#(.*?)\.(svg)$#is', $filename, $matches)) {

			$thumb_url = $image_url;

		}

	}

	return $thumb_url;

}


/**
 * Get registered image sizes with dimensions
 *
 * @param bool $include_hidden Include hidden thumbnail sizes to the result
 *
 * @return array
 */

function tw_get_thumb_sizes($include_hidden = true) {

	$sizes = tw_get_setting('cache', 'thumb_sizes_registered');

	if (!$sizes) {

		$sizes_default = array('thumbnail', 'medium', 'medium_large', 'large');

		foreach ($sizes_default as $size) {

			$sizes[$size] = array(
				'width' => get_option($size . '_size_w'),
				'height' => get_option($size . '_size_h'),
				'crop' => get_option($size . '_crop')
			);

		}

		$sizes = array_merge($sizes, wp_get_additional_image_sizes());

		tw_set_setting('cache', 'thumb_sizes_registered', $sizes);

	}

	if ($include_hidden) {

		$sizes_hidden = tw_get_setting('cache', 'thumb_sizes_hidden');

		if (!$sizes_hidden) {

			$sizes_hidden = array();

			$sizes_registered = tw_get_setting('thumbs');

			if ($sizes_registered and is_array($sizes_registered)) {

				foreach ($sizes_registered as $name => $size) {

					if (!empty($size['hidden'])) {

						$sizes_hidden[$name] = $size;

					}

				}

			}

			tw_set_setting('cache', 'thumb_sizes_hidden', $sizes_hidden);

		}

		$sizes = array_merge($sizes, $sizes_hidden);

	}

	return $sizes;

}


/**
 * Get the thumbnail url
 *
 * @param int|array|WP_Post $image WordPress Post object, ACF image array or an attachment ID
 * @param string|array      $size  Size of the thumbnail
 *
 * @return string
 */

function tw_get_thumb_link($image, $size = 'full') {

	$thumb_url = '';

	if (empty($image)) {
		return $thumb_url;
	}

	if ($image instanceof WP_Post) {
		$image = get_post_meta($image->ID, '_thumbnail_id', true);
	}

	if (is_string($image) and !is_numeric($image)) {

		$path = '/assets/images/' . $image;

		if (file_exists(TW_ROOT . $path)) {
			$thumb_url = tw_create_thumb(get_template_directory_uri() . $path, $size);
		}

	} else {

		$sizes = tw_get_thumb_sizes(false);

		if (is_string($size) and (isset($sizes[$size]) or $size == 'full')) {

			if (is_array($image) and !empty($image['url'])) {

				if (!empty($image['sizes'][$size])) {

					$thumb_url = $image['sizes'][$size];

				} else {

					$thumb_url = $image['url'];

				}

			} elseif (is_numeric($image)) {

				$thumb_url = wp_get_attachment_image_url($image, $size);

			}

		} else {

			if (is_array($image) and !empty($image['url'])) {

				$thumb_url = tw_create_thumb($image['url'], $size, $image['id']);

			} elseif (is_numeric($image)) {

				$thumb_url = tw_create_thumb(wp_get_attachment_image_url($image, 'full'), $size, $image);

			}

		}

	}

	return $thumb_url;

}


/**
 * Get the thumbnail with given size
 *
 * @param int|array|WP_Post $image      A post object, ACF image or an attachment ID
 * @param string|array      $size       Size of the image
 * @param string            $before     Code before thumbnail
 * @param string            $after      Code after thumbnail
 * @param array             $attributes Array with attributes
 *
 * @return string
 */

function tw_thumb($image, $size = 'full', $before = '', $after = '', $attributes = array()) {

	$thumb = tw_get_thumb_link($image, $size);

	if ($thumb) {

		$link_href = false;
		$link_image_size = false;

		if (!empty($attributes['link'])) {

			if ($attributes['link'] == 'url' and $image instanceof WP_Post) {

				$link_href = get_permalink($image);

			} else {

				$sizes = tw_get_thumb_sizes();

				if (is_array($attributes['link']) or $attributes['link'] == 'full' or !empty($sizes[$attributes['link']])) {
					$link_image_size = $attributes['link'];
				} else {
					$link_href = $attributes['link'];
				}

			}

		}

		if ($image instanceof WP_Post) {
			$image = get_post_meta($image->ID, '_thumbnail_id', true);
		} elseif (is_array($image) and !empty($image['id'])) {
			$image = $image['id'];
		}

		if (is_numeric($image)) {
			$attributes['alt'] = trim(strip_tags(get_post_meta($image, '_wp_attachment_image_alt', true)));
		} elseif (empty($attributes['alt'])) {
			$attributes['alt'] = '';
		}

		if ($link_image_size and !$link_href) {
			$link_href = tw_get_thumb_link($image, $link_image_size);
		}

		if ($link_href) {

			$link_class = '';

			if (!empty($attributes['link_class'])) {
				$link_class = ' class="' . $attributes['link_class'] . '"';
			}

			$before = $before . '<a href="' . $link_href . '"' . $link_class . '>';
			$after = '</a>' . $after;

		}

		if (!empty($attributes['lazy'])) {
			$attributes['loading'] = 'lazy';
		}

		if (!empty($attributes['before'])) {
			$before = $before . $attributes['before'];
		}

		if (!empty($attributes['after'])) {
			$after = $attributes['after'] . $after;
		}

		$data = array();
		$list = array('loading', 'alt', 'class', 'id', 'width', 'height', 'style');

		foreach ($attributes as $key => $attribute) {
			if (in_array($key, $list) or strpos($attribute, 'data') === 0) {
				$data[] = $key . '="' . esc_attr($attribute) . '"';
			}
		}

		if ($data) {
			$data = ' ' . implode(' ', $data);
		} else {
			$data = '';
		}

		if ($thumb) {
			$thumb = $before . '<img src="' . $thumb . '"' . $data . ' />' . $after;
		}

	}

	return $thumb;

}


/**
 * Get the thumbnail as a background image
 *
 * @param int|array|WP_Post $image A post object, ACF image or an attachment ID
 * @param string|array      $size  Size of the image
 * @param bool              $style Include the style attribute
 *
 * @return string
 */

function tw_thumb_background($image, $size = 'full', $style = true) {

	$thumb = tw_get_thumb_link($image, $size);

	if ($thumb) {

		$thumb = 'background-image: url(' . esc_url($thumb) . ');';

		if ($style) {
			$thumb = ' style="' . $thumb . '"';
		}

	}

	return $thumb;

}