<?php
/**
 * Thumbnail processing library
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
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
 * @param string $image_url URL of the image
 * @param array|string $size Size of the image
 *
 * @return string
 */

function tw_create_thumb($image_url, $size) {

	global $_wp_additional_image_sizes;

	$result = '';

	$position = mb_strrpos($image_url, '/');

	if ($position < mb_strlen($image_url)) {

		$filename = mb_strtolower(mb_substr($image_url, $position + 1));

		if (preg_match('#(.*?)\.(gif|jpg|jpeg|png|bmp)$#is', $filename, $matches)) {

			if (is_array($size) or (is_string($size) and $size != 'full')) {

				$crop = true;
				$width = 0;
				$height = 0;

				if (is_string($size) and !empty($size) and !empty($_wp_additional_image_sizes[$size])) {

					$width = $_wp_additional_image_sizes[$size]['width'];
					$height = $_wp_additional_image_sizes[$size]['height'];
					$crop = $_wp_additional_image_sizes[$size]['crop'];

				} elseif (is_array($size) and $size) {

					if (isset($size[0])) {
						$width = $size[0];
					}

					if (isset($size[1])) {
						$height = $size[1];
					}

					if (isset($size[2])) {
						$crop = $size[2];
					}

				} else {

					$width = $_wp_additional_image_sizes['thumbnail']['width'];
					$height = $_wp_additional_image_sizes['thumbnail']['height'];
					$crop = $_wp_additional_image_sizes['thumbnail']['crop'];

				}

				$width = intval($width);
				$height = intval($height);

				$filename = '/includes/cache/' . $matches[1] . '-' . $width . '-' . $height . '.' . $matches[2];

				if (!is_file(get_template_directory() . $filename)) {

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
						$editor->save(get_template_directory() . $filename);
					} else {
						return $image_url;
					}

				}

				$result = get_template_directory_uri() . $filename;

			} else {

				$result = $image_url;

			}

		}

	}

	return $result;

}


/**
 * Get the thumbnail for a given post
 *
 * @param bool|WP_Post $post Post object or false to use the current one
 * @param string|array $size Size of the thumbnail
 * @param string $before     Code to prepend to the breadcrumbs
 * @param string $after      Code to append to the breadcrumbs
 * @param array $atts        Array with attributes
 * @param bool $thumb_only   Do not find the images in the post content
 *
 * @return string
 */

function tw_thumb($post = false, $size = '', $before = '', $after = '', $atts = array(), $thumb_only = false) {

	global $_wp_additional_image_sizes;

	$thumb = '';
	$link_href = false;
	$link_image_size = false;

	if ($post == false) {
		$post = get_post();
	}

	if (!empty($atts['link'])) {

		if ($atts['link'] == 'url' and !empty($post->ID)) {

			$link_href = get_permalink($post->ID);

		} else {

			if (!empty($_wp_additional_image_sizes[$atts['link']])) {
				$link_image_size = $atts['link'];
			} else {
				$link_image_size = 'full';
			}

		}

		unset($atts['link']);

	}

	if (empty($atts['link_class'])) {

		$class = '';

	} else {

		$class = ' class="' . $atts['link_class'] . '"';

		unset($atts['link_class']);

	}

	if (!$size or (is_string($size) and empty($_wp_additional_image_sizes[$size]) and !in_array($size, array('thumbnail', 'medium', 'large', 'full')))) {
		$size = 'thumbnail';
	}

	if (is_object($post) and !empty($post->ID) and has_post_thumbnail($post->ID)) {

		if ($link_image_size) {

			$thumb = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $link_image_size);

			if (!empty($thumb[0])) {
				$link_href = $thumb[0];
			}

		}

		$thumb = get_the_post_thumbnail($post->ID, $size, $atts);

	} elseif (is_array($post) and !empty($post['url'])) {

		if (is_string($size) and !empty($post['sizes'][$size])) {
			$image = $post['sizes'][$size];
		} else {
			$image = tw_create_thumb($post['url'], $size);
		}

		if ($image) {

			if ($link_image_size and !$link_href) {
				$link_href = tw_create_thumb($post['url'], $link_image_size);
			}

			$thumb = '<img src="' . $image . '" alt="' . $post['alt'] . '"' . ((!empty($atts['class'])) ? ' class="' . $atts['class'] . '"' : '') . ' />';

		}

	} elseif (!$thumb_only) {

		$image = tw_create_thumb(tw_find_image($post->post_content), $size);

		if ($image) {

			if ($link_image_size and !$link_href) {
				$link_href = tw_create_thumb($image, $link_image_size);
			}

			$thumb = '<img src="' . $image . '" alt="' . $post->post_title . '"' . ((!empty($atts['class'])) ? ' class="' . $atts['class'] . '"' : '') . ' />';

		}

	}

	if ($link_href) {
		$before = $before . '<a href="' . $link_href . '"' . $class . '>';
		$after = '</a>' . $after;
	}

	if ($thumb) {
		$thumb = $before . $thumb . $after;
	}

	return $thumb;

}