<?php
/**
 * Thumbnail processing library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
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
 * @param string       $image_url Image url
 * @param array|string $size      Size of the image
 *
 * @return string
 */

function tw_create_thumb($image_url, $size) {

	$result = '';

	$position = mb_strrpos($image_url, '/');

	if ($position < mb_strlen($image_url)) {

		$filename = mb_strtolower(mb_substr($image_url, $position + 1));

		if (preg_match('#(.*?)\.(gif|jpg|jpeg|png|bmp)$#is', $filename, $matches)) {

			$sizes = tw_get_thumb_sizes(true);

			$width = 0;
			$height = 0;
			$crop = true;

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

				$filename = '/cache/' . $matches[1] . '-' . $width . 'x' . $height . '-' . hash('crc32', $image_url, false) . '.' . $matches[2];

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
						$editor->resize($width, $height, $crop);
						$editor->save($directory . $filename);
					} else {
						return $image_url;
					}

				}

				$result = $directory_uri . $filename;

			} else {

				$result = $image_url;

			}

		}

	}

	return $result;

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

function tw_get_thumb_link($image, $size) {

	if (is_object($image) and !empty($image->ID) and has_post_thumbnail($image->ID)) {
		$image = get_post_thumbnail_id($image->ID);
	}

	$sizes = tw_get_thumb_sizes(false);

	if (is_string($size) and (isset($sizes[$size]) or $size == 'full')) {

		if (is_array($image) and !empty($image['url'])) {

			if (!empty($image['sizes'][$size])) {

				$url = $image['sizes'][$size];

			} else {

				$url = $image['url'];

			}

		} else {

			$url = wp_get_attachment_image_url($image, $size);

		}

	} else {

		if (is_array($image) and !empty($image['url'])) {

			$url = tw_create_thumb($image['url'], $size);

		} else {

			$url = tw_create_thumb(wp_get_attachment_image_url($image, 'full'), $size);

		}

	}

	return $url;

}


/**
 * Get the thumbnail with given size
 *
 * @param int|array|WP_Post $image             WordPress Post object, ACF image array or an attachment ID
 * @param string|array      $size              Size of the thumbnail
 * @param string            $before            Code before thumbnail
 * @param string            $after             Code after thumbnail
 * @param array             $atts              Array with attributes
 * @param bool              $search_in_content Search the images in the post content
 *
 * @return string
 */

function tw_thumb($image, $size = '', $before = '', $after = '', $atts = array(), $search_in_content = false) {

	$alt = '';
	$thumb_class = '';
	$link_class = '';
	$link_href = false;
	$link_image_size = false;

	if (!empty($atts['link'])) {

		if ($atts['link'] == 'url' and !empty($image->ID)) {

			$link_href = get_permalink($image);

		} else {

			$sizes = tw_get_thumb_sizes();

			if (is_array($atts['link']) or $atts['link'] == 'full' or !empty($sizes[$atts['link']])) {
				$link_image_size = $atts['link'];
			} else {
				$link_href = $atts['link'];
			}

		}

	}

	if (!empty($atts['link_class'])) {
		$link_class = ' class="' . $atts['link_class'] . '"';
	}

	if (!empty($atts['class'])) {
		$thumb_class = ' class="' . $atts['class'] . '"';
	}

	$thumb = tw_get_thumb_link($image, $size);

	if (!$thumb and $search_in_content and !empty($image->post_content)) {
		$thumb = tw_create_thumb(tw_find_image($image->post_content), $size);
	}

	if (is_object($image) and !empty($image->ID) and has_post_thumbnail($image)) {
		$image = get_post_thumbnail_id($image);
	} elseif (is_array($image) and !empty($image['id'])) {
		$image = $image['id'];
	}

	if (is_numeric($image)) {
		$alt = trim(strip_tags(get_post_meta($image, '_wp_attachment_image_alt', true)));
	}

	if ($link_image_size and !$link_href) {
		$link_href = tw_get_thumb_link($image, $link_image_size);
	}

	if ($link_href) {
		$before = $before . '<a href="' . $link_href . '"' . $link_class . '>';
		$after = '</a>' . $after;
	}

	if ($thumb) {
		$thumb = $before . '<img src="' . $thumb . '" alt="' . $alt . '"' . $thumb_class . ' />' . $after;
	}

	return $thumb;

}