<?php
/**
 * Image Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */

namespace Twee;

use WP_Post;

class Image {

	protected $sizes = [
		'hidden' => [],
		'custom' => [],
		'registered' => []
	];

	protected $upload_dir = '';

	protected $upload_url = '';

	public function __construct() {

		add_action('twee_thumb_created', [$this, 'compressImage'], 10, 3);

		add_filter('image_size_names_choose', [$this, 'filterSizes']);

		$upload_dir = wp_upload_dir();

		if (!empty($upload_dir['basedir']) and !empty($upload_dir['baseurl'])) {
			$this->upload_dir = $upload_dir['basedir'];
			$this->upload_url = $upload_dir['baseurl'];
		} else {
			$this->upload_dir = get_template_directory();
			$this->upload_url = get_stylesheet_directory_uri();
		}

	}


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
	public function getThumb($image, $size = 'full', $before = '', $after = '', $attributes = []) {

		$thumb = $this->getLink($image, $size);

		if ($thumb) {

			$link_href = false;
			$link_image_size = false;

			if (!empty($attributes['link'])) {

				if ($attributes['link'] == 'url' and $image instanceof WP_Post) {

					$link_href = get_permalink($image);

				} else {

					$sizes = $this->getSizes();

					if (is_array($attributes['link']) or $attributes['link'] == 'full' or !empty($sizes[$attributes['link']])) {
						$link_image_size = $attributes['link'];
					} else {
						$link_href = $attributes['link'];
					}

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

			if (!isset($attributes['alt'])) {
				if (is_numeric($image)) {
					$attributes['alt'] = get_post_meta($image, '_wp_attachment_image_alt', true);
				} else {
					$attributes['alt'] = '';
				}
			}

			if ($link_image_size and !$link_href) {
				$link_href = $this->getLink($image, $link_image_size);
			}

			if ($link_href) {

				$link_class = '';

				if (!empty($attributes['link_class'])) {
					$link_class = ' class="' . $attributes['link_class'] . '"';
				}

				$before = $before . '<a href="' . esc_url($link_href) . '"' . $link_class . '>';
				$after = '</a>' . $after;

			}

			if (empty($attributes['loading'])) {
				$attributes['loading'] = 'lazy';
			}

			if (!empty($attributes['before'])) {
				$before = $before . $attributes['before'];
			}

			if (!empty($attributes['after'])) {
				$after = $attributes['after'] . $after;
			}

			$data = $this->getSize($size, $image);

			if ($data['width'] > 0 and $data['height'] > 0) {
				$attributes['width'] = $data['width'];
				$attributes['height'] = $data['height'];
			}

			$data = [];
			$list = ['loading', 'alt', 'class', 'id', 'width', 'height', 'style'];

			foreach ($attributes as $key => $attribute) {
				if (in_array($key, $list) or strpos($attribute, 'data') === 0) {
					$data[] = $key . '="' . esc_attr(strip_tags($attribute)) . '"';
				}
			}

			if ($data) {
				$data = ' ' . implode(' ', $data);
			} else {
				$data = '';
			}

			$thumb = $before . '<img src="' . $thumb . '"' . $data . ' />' . $after;

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
	public function getBackground($image, $size = 'full', $style = true) {

		$thumb = $this->getLink($image, $size);

		if ($thumb) {

			$thumb = 'background-image: url(\'' . esc_attr($thumb) . '\');';

			if ($style) {
				$thumb = ' style="' . $thumb . '"';
			}

		}

		return $thumb;

	}


	/**
	 * Get the thumbnail url
	 *
	 * @param int|array|WP_Post $image WordPress Post object, ACF image array or an attachment ID
	 * @param string|array      $size  Size of the thumbnail
	 *
	 * @return string
	 */
	public function getLink($image, $size = 'full') {

		$thumb_url = '';

		if ($image instanceof WP_Post) {
			if ($image->post_type === 'attachment') {
				$image = $image->ID;
			} else {
				$image = intval(get_post_meta($image->ID, '_thumbnail_id', true));
			}
		}

		if (is_array($image) and !empty($image['sizes']) and !empty($image['ID'])) {

			if (is_string($size) and !empty($image['sizes'][$size])) {
				$thumb_url = $image['sizes'][$size];
			} else {
				$image = intval($image['ID']);
			}

		}

		if (empty($image)) {
			return $thumb_url;
		}

		if (is_numeric($image)) {

			$file = get_post_meta($image, '_wp_attached_file', true);

			if ($file) {

				if (0 === strpos($file, $this->upload_dir)) {
					$image_url = str_replace($this->upload_dir, $this->upload_url, $file);
				} elseif (strpos($file, 'http') === 0 or strpos($file, '//') === 0) {
					$image_url = $file;
				} else {
					$image_url = $this->upload_url . '/' . $file;
				}

				if (is_string($size) and $size !== 'full') {

					$meta = get_post_meta($image, '_wp_attachment_metadata', true);

					if (is_array($meta) and !empty($meta['sizes'][$size]) and !empty($meta['sizes'][$size]['file'])) {
						$thumb_url = path_join(dirname($image_url), $meta['sizes'][$size]['file']);
					} else {
						$thumb_url = $this->createThumb($image_url, $size, $image);
					}

				} elseif (is_array($size)) {

					$thumb_url = $this->createThumb($image_url, $size, $image);

				} else {

					$thumb_url = $image_url;

				}

			}

		} elseif (is_string($image)) {

			if (strpos($image, 'http') === 0 or strpos($image, '//') === 0) {

				$thumb_url = $this->createThumb($image, $size);

			} else {

				$path = 'assets/images/' . $image;

				if (file_exists(TW_ROOT . $path)) {
					$thumb_url = $this->createThumb(TW_URL . $path, $size);
				}

			}

			$image = 0;

		}

		return apply_filters('wp_get_attachment_url', $thumb_url, $image);

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
	public function createThumb($image_url, $size, $image_id = 0) {

		$thumb_url = '';

		if (empty($image_url)) {
			return $thumb_url;
		}

		$position = mb_strrpos($image_url, '/');

		if ($position < mb_strlen($image_url)) {

			$filename = mb_strtolower(mb_substr($image_url, $position + 1));

			if (preg_match('#(.*?)\.(gif|jpg|jpeg|png|bmp|webp)$#is', $filename, $matches)) {

				$data = $this->getSize($size);

				$width = $data['width'];
				$height = $data['height'];
				$crop = $data['crop'];

				$thumb_url = $image_url;

				if ($width > 0 or $height > 0) {

					if ($image_id > 0) {
						$image_id = $image_id . '_';
						$url_hash = '';
					} else {
						$image_id = '';
						$url_hash = '_' . hash('crc32', $image_url, false);
					}

					if (is_array($crop)) {
						$crop_hash = '_' . implode('_', $crop);
					} else {
						$crop_hash = '';
					}

					$filename = '/cache/thumbs_' . $width . 'x' . $height . '/' . $image_id . $matches[1] . $url_hash . $crop_hash . '.' . $matches[2];

					if (!is_file($this->upload_dir . $filename)) {

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

							$size = $editor->get_size();

							if (!empty($size['width']) and !empty($size['height'])) {
								$size = $this->calculateSize($size['width'], $size['height'], $width, $height, $data['crop'], $data['aspect']);
								$width = $size['width'];
								$height = $size['height'];
							}

							$editor->resize($width, $height, $crop);

							$editor->save($this->upload_dir . $filename);

							do_action('twee_thumb_created', $this->upload_dir . $filename, $this->upload_url . $filename, $image_id);

						} else {

							return $image_url;

						}

					}

					$thumb_url = $this->upload_url . $filename;

				}

			} elseif (preg_match('#(.*?)\.(svg)$#is', $filename, $matches)) {

				$thumb_url = $image_url;

			}

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
	public function calculateSize($image_width, $image_height, $thumb_width, $thumb_height, $crop = true, $aspect = false) {

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

			if ($image_ratio < $thumb_ratio) {
				$thumb_width = $thumb_width * $image_ratio / $thumb_ratio;
			} else {
				$thumb_height = $thumb_height * $image_ratio / $thumb_ratio;
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
	 * Register a new thumbnail size
	 *
	 * @param string $name Size label
	 * @param array  $data Array with thumbnail data
	 *
	 * @return void
	 */
	public function addSize($name, $data) {

		if (empty($name) or !is_string($name) or !is_array($data)) {
			return;
		}

		if (empty($data['hidden'])) {

			if (!isset($data['crop'])) {
				$data['crop'] = true;
			}

			if (!isset($data['width'])) {
				$data['width'] = 0;
			}

			if (!isset($data['height'])) {
				$data['height'] = 0;
			}

			if (in_array($name, ['thumbnail', 'medium', 'medium_large', 'large'])) {

				if (get_option($name . '_size_w') != $data['width']) {
					update_option($name . '_size_w', $data['width']);
				}

				if (get_option($name . '_size_h') != $data['height']) {
					update_option($name . '_size_h', $data['height']);
				}

				if (isset($data['crop']) and get_option($name . '_crop') != $data['crop']) {
					update_option($name . '_crop', $data['crop']);
				}

			} else {

				add_image_size($name, $data['width'], $data['height'], $data['crop']);

			}

			if (isset($data['thumb']) and $data['thumb']) {

				set_post_thumbnail_size($data['width'], $data['height'], $data['crop']);

			}

		} else {

			$this->sizes['hidden'][$name] = $data;

		}

		$this->sizes['custom'][$name] = $data;

	}


	/**
	 * Register a set of thumbnail sizes
	 *
	 * @param array $sizes Array with sizes
	 *
	 * @return void
	 */
	public function addSizes($sizes) {

		if (is_array($sizes)) {
			foreach ($sizes as $size => $data) {
				$this->addSize($size, $data);
			}
		}

	}


	/**
	 * Get the image size
	 *
	 * @param string|array $size
	 * @param int          $image_id
	 *
	 * @return array
	 */
	public function getSize($size, $image_id = 0) {

		$sizes = $this->getSizes(true);

		$result = [
			'width' => 0,
			'height' => 0,
			'crop' => true,
			'aspect' => false
		];

		if (is_array($size)) {
			$result['width'] = (isset($size[0])) ? intval($size[0]) : 0;
			$result['height'] = (isset($size[1])) ? intval($size[1]) : 0;
			$result['crop'] = (isset($size[2])) ? $size[2] : true;
			$result['keep'] = (isset($size[3])) ? $size[3] : true;
		} elseif (is_string($size) and isset($sizes[$size]) and $size != 'full') {
			$result['width'] = (isset($sizes[$size]['width'])) ? intval($sizes[$size]['width']) : 0;
			$result['height'] = (isset($sizes[$size]['height'])) ? intval($sizes[$size]['height']) : 0;
			$result['crop'] = (isset($sizes[$size]['crop'])) ? $sizes[$size]['crop'] : true;
			$result['aspect'] = (isset($sizes[$size]['aspect'])) ? $sizes[$size]['aspect'] : false;
		}

		if ($image_id > 0) {

			$meta = get_post_meta($image_id, '_wp_attachment_metadata', true);

			if (is_array($meta) and !empty($meta['width']) and !empty($meta['height'])) {

				if ($size === 'full') {
					$result['width'] = $meta['width'];
					$result['height'] = $meta['height'];
				} elseif (!empty($meta['sizes']) and is_string($size) and !empty($meta['sizes'][$size])) {
					$result['width'] = $meta['sizes'][$size]['width'];
					$result['height'] = $meta['sizes'][$size]['height'];
				} else {
					$size = $this->calculateSize($meta['width'], $meta['height'], $result['width'], $result['height'], $result['crop'], $result['aspect']);
					$result['width'] = $size['width'];
					$result['height'] = $size['height'];
				}

			}

		}

		return $result;

	}


	/**
	 * Get registered image sizes with dimensions
	 *
	 * @param bool $hidden Include the hidden thumbnail sizes
	 *
	 * @return array
	 */
	public function getSizes($hidden = true) {

		$sizes = $this->sizes['registered'];

		if (empty($this->sizes['registered'])) {

			$default = ['thumbnail', 'medium', 'medium_large', 'large'];

			foreach ($default as $size) {

				$sizes[$size] = [
					'width' => get_option($size . '_size_w'),
					'height' => get_option($size . '_size_h'),
					'crop' => get_option($size . '_crop')
				];

			}

			$sizes = array_merge($sizes, wp_get_additional_image_sizes());

			$this->sizes['registered'] = $sizes;

		}

		if ($hidden and !empty($this->sizes['hidden'])) {
			$sizes = array_merge($sizes, $this->sizes['hidden']);
		}

		return $sizes;

	}


	/**
	 * Add the registered image sizes to the media editor
	 *
	 * @param $sizes array
	 *
	 * @return array
	 */
	public function filterSizes($sizes) {

		if ($this->sizes['custom']) {

			foreach ($this->sizes['custom'] as $name => $size) {

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

	}


	/**
	 * Compress the image using popular compressing plugins
	 *
	 * @param string $file     Full path to the image
	 * @param string $url      Image URL
	 * @param int    $image_id Image ID
	 */
	public function compressImage($file, $url, $image_id = 0) {

		if (is_readable($file)) {

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
					} catch (\Tiny_Exception $e) {

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

				$file->optimize([
					'backup' => false,
					'optimization_level' => 1,
					'keep_exif' => false,
					'convert' => '',
					'context' => 'wp',
				]);

			}

		}

	}

}