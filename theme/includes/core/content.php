<?php
/**
 * Text Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.3
 */

/**
 * Get a title of a post or term
 *
 * @param object $object
 * @param int    $length
 *
 * @return string
 */
function tw_content_title(object $object, int $length = 0): string
{
	$title = '';

	if ($object instanceof WP_Post) {
		$title = apply_filters('the_title', $object->post_title, $object->ID);
	} elseif ($object instanceof WP_Term) {
		$title = $object->name;
	} elseif ($object instanceof WP_Post_Type) {
		$title = $object->label;
	} elseif ($object instanceof WP_User) {
		$title = $object->display_name;
	}

	if ($title and $length > 0) {
		$title = tw_content_strip($title, $length);
	}

	return $title;
}


/**
 * Get a short description for a given object or a string
 *
 * @param string|WP_Post|WP_Term $object       Post, Term, User, or a string
 * @param int                    $length       Required length of the text
 * @param bool|string            $allowed_tags List of tags separated by "|"
 * @param string                 $find         Symbol to find for proper strip
 * @param bool                   $force_cut    Strip the post excerpt
 *
 * @return string
 */
function tw_content_text($object, int $length = 250, $allowed_tags = false, string $find = ' ', bool $force_cut = true): string
{
	$excerpt = false;
	$text = '';

	if ($object instanceof WP_Post) {
		$text = $object->post_content;
		$excerpt = $object->post_excerpt;
	} elseif ($object instanceof WP_Term) {
		$text = $object->description;
	} elseif (is_string($object)) {
		$text = $object;
	}

	if (empty($length)) {
		$result = $text;

		if (!empty($excerpt)) {
			$result = $excerpt;
		}
	} elseif ($excerpt and mb_strlen($excerpt) > 10) {
		$result = $force_cut ? tw_content_strip($excerpt, $length, $allowed_tags, $find) : $excerpt;
	} elseif (mb_strpos($text, '<!--more') !== false) {
		$position = mb_strpos($text, '<!--more');

		if ($force_cut) {
			$result = tw_content_strip(mb_substr($text, 0, $position), $length, $allowed_tags, $find);
		} else {
			$result = tw_content_strip(mb_substr($text, 0, $position), $position, $allowed_tags, $find);
		}
	} else {
		$result = tw_content_strip($text, $length, $allowed_tags, $find);
	}

	return $result;
}


/**
 * Render the ACF link field
 *
 * @param array  $link
 * @param string $class
 * @param string $hidden
 *
 * @return string
 */
function tw_content_link(array $link, string $class = 'button', string $hidden = ''): string
{
	$result = '';

	if (empty($link['url']) or !isset($link['title'])) {
		return $result;
	}

	if ($class) {
		$class = ' class="' . $class . '"';
	} else {
		$class = '';
	}

	if (!empty($link['target'])) {
		$target = ' target="' . $link['target'] . '"';
	} else {
		$target = '';
	}

	if ($hidden) {
		$hidden = '<span class="sr-hidden">' . $hidden . '</span>';
	}

	return '<a href="' . esc_url($link['url']) . '"' . $class . $target . '>' . $link['title'] . $hidden . '</a>';
}


/**
 * Get the title for the current page
 *
 * @param WP_Query|false $query
 * @param string         $before          Code to prepend to the title
 * @param string         $after           Code to append to the title
 * @param bool           $add_page_number Add a page number to the title
 *
 * @return string
 */
function tw_content_heading($query = false, string $before = '', string $after = '', bool $add_page_number = false)
{
	$title = '';

	if ($query === false) {
		global $wp_query;
		$query = $wp_query;
	}

	if (!($query instanceof WP_Query)) {
		return $title;
	}

	$object = $query->get_queried_object();

	if ($query->is_front_page()) {
		$title = get_bloginfo('name', 'display');
	} elseif ($query->is_404()) {
		$title = __('Page not found', 'twee');
	} elseif ($query->is_search()) {
		$title = sprintf(__('Search results for %s', 'twee'), get_search_query());
	} elseif ($object instanceof WP_Term) {
		$title = $object->name;
	} elseif ($object instanceof WP_Post) {
		$title = $object->post_title;
	} elseif ($object instanceof WP_Post_Type) {
		$title = $object->label;
	} elseif ($object instanceof WP_User) {
		$title = sprintf(__('Posts of <i>%s</i>', 'twee'), $object->display_name);
	} elseif ($query->is_day() and $query->post instanceof WP_Post) {
		$title = sprintf(__('Posts for <i>%s</i>', 'twee'), mysql2date('F j, Y', $query->post->post_date));
	} elseif ($query->is_month() and $query->post instanceof WP_Post) {
		$title = sprintf(__('Posts for <i>%s</i>', 'twee'), mysql2date('F Y', $query->post->post_date));
	} elseif ($query->is_year() and $query->post instanceof WP_Post) {
		$title = sprintf(__('Posts for <i>%s</i> year', 'twee'), mysql2date('Y', $query->post->post_date));
	}

	if ($add_page_number and !empty($title)) {

		$page = (int) $query->get('paged', 1);

		if ($page > 1) {
			$title .= sprintf(__(' - Page %d', 'twee'), $page);
		}

	}

	if (!empty($title)) {
		$title = $before . $title . $after;
	}

	return $title;
}


/**
 * Strip the text to a given length
 *
 * @param string      $text         Text to strip
 * @param int         $length       Required length of the text
 * @param bool|string $allowed_tags List of tags separated by "|"
 * @param string      $find         Symbol to find for proper strip
 * @param string      $dots         Text after the stripped text
 *
 * @return string
 */
function tw_content_strip(string $text, int $length = 200, $allowed_tags = false, string $find = ' ', string $dots = '...')
{
	if ($allowed_tags) {

		$allowed_tags_list = 'i|em|b|strong|s|del';

		if ($allowed_tags != '+' and mb_strpos($allowed_tags, '+') === 0) {
			$allowed_tags = str_replace('+', '', $allowed_tags);
			$allowed_tags_list .= '|' . $allowed_tags;
		} else {
			$allowed_tags_list = $allowed_tags;
		}

		$allowed_tags_list = '<' . implode('><', explode('|', $allowed_tags_list)) . '>';

	} elseif ($allowed_tags === false) {
		$allowed_tags_list = '<i><em><b><strong><s><del>';
	} else {
		$allowed_tags_list = '';
	}

	$tags = ['style', 'script', 'h1', 'h2', 'h3'];

	foreach ($tags as $tag) {
		$text = preg_replace("#<{$tag}[^>]*?>.*?</{$tag}>#is", '', $text);
	}

	$text = trim(strip_tags(strip_shortcodes($text), $allowed_tags_list));

	if ($find and mb_strlen($text) > $length) {

		$pos = mb_strpos($text, $find, $length);

		if ($pos < $length or $pos > ($length + 20)) {
			$pos = $length;
		}

		$text = mb_substr($text, 0, $pos);

		preg_match('#(.+)</[^>]+$#s', $text, $matches);

		if (!empty($matches[1])) {
			$text = $matches[1];
		}

		$text .= $dots;

	} else {

		$pos = $length;
		$text = mb_substr($text, 0, $pos);

	}

	if ($allowed_tags_list) {

		if (mb_strpos($allowed_tags_list, '<a>') !== false) {

			$link_start = mb_strrpos($text, '<a');

			if ($link_start !== false) {

				$link_end = mb_strpos($text, '</a>', $link_start);

				if ($link_end === false) {
					$text = mb_substr($text, 0, $link_start) . $dots;
				}

			}

			$text = preg_replace('#<a[^>]*?></a>#i', '', $text);

		}

		$text = force_balance_tags($text);

	}

	return $text;
}


/**
 * Get the formatted phone number for a link href attribute
 *
 * @param string $string String with phone number
 *
 * @return string
 */
function tw_content_phone(string $string): string
{
	$phone = preg_replace('/(?<!^)\+|[^\d+]/', '', $string);

	return strlen($phone) > 4 ? 'tel:' . $phone : '#';
}


/**
 * Get the formatted date and time for a post
 *
 * @param WP_Post $post   Post object of false to use the current one
 * @param string  $format Date and time format
 *
 * @return string
 */
function tw_content_date(WP_Post $post, string $format = ''): string
{
	if (!$format) {
		$format = (string) get_option('date_format', 'Y-m-d H:i:s');
	}

	$date = mysql2date($format, $post->post_date);

	return apply_filters('get_the_date', $date, $format);
}


/**
 * Get the reading time
 *
 * @param WP_Post $post
 * @param string  $label
 *
 * @return string
 */
function tw_content_time(WP_Post $post, string $label = ' min read'): string
{
	$word_count = str_word_count(strip_tags($post->post_content));

	$time = ceil($word_count / 200);

	if ($time < 3) {
		$time = 3;
	}

	return $time . $label;
}


/**
 * Generate an embed code for YouTube, Vimeo, or native HTML5 video
 *
 * @param int|string|array $video Video URL, Attachment ID, or ACF File Array.
 * @param array            $args  Configuration options for the video playback.
 *
 * @return string                 The generated iframe or video HTML.
 */
function tw_content_video(int|string|array $video, array $args = []): string
{
	if (empty($video)) {
		return '';
	}

	$url = '';
	$mime = '';

	// Normalize an Attachment ID into an ACF File Array
	if (is_numeric($video)) {
		if (function_exists('acf_get_attachment')) {
			$video = acf_get_attachment($video);
		} else {
			$url = wp_get_attachment_url($video);
			$mime = get_post_mime_type($video) ? : '';
		}
	}

	// Extract data from the ACF File Array (or custom array)
	if (is_array($video) and !empty($video['url'])) {
		$url = $video['url'];
		$mime = $video['mime_type'] ?? '';

		// Automatically use native attachment dimensions if not overridden in $args
		if (empty($args['width']) and !empty($video['width'])) {
			$args['width'] = $video['width'];
		}
		if (empty($args['height']) and !empty($video['height'])) {
			$args['height'] = $video['height'];
		}
	} elseif (is_string($video) and !is_numeric($video)) {
		$url = $video;
	}

	if (empty($url)) {
		return '';
	}

	$defaults = [
		'autoplay'    => true,
		'muted'       => true,
		'loop'        => true,
		'controls'    => false,
		'playsinline' => true,
		'class'       => 'video',
		'width'       => '',
		'height'      => '',
		'poster'      => '',
	];

	$args = array_merge($defaults, $args);

	$width = !empty($args['width']) ? ' width="' . htmlspecialchars((string) $args['width'], ENT_QUOTES) . '"' : '';
	$height = !empty($args['height']) ? ' height="' . htmlspecialchars((string) $args['height'], ENT_QUOTES) . '"' : '';

	if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*?&)?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $url, $match)) {
		$video_id = $match[1];

		$params = [
			'autoplay'       => $args['autoplay'] ? 1 : 0,
			'mute'           => $args['muted'] ? 1 : 0,
			'controls'       => $args['controls'] ? 1 : 0,
			'playsinline'    => $args['playsinline'] ? 1 : 0,
			'rel'            => 0,
			'iv_load_policy' => 3,
		];

		// YouTube requires the 'playlist' parameter to loop a single video
		if ($args['loop']) {
			$params['loop'] = 1;
			$params['playlist'] = $video_id;
		}

		$iframe_url = 'https://www.youtube-nocookie.com/embed/' . $video_id . '?' . http_build_query($params);
		$video_type = 'youtube';

	} elseif (preg_match('#(?:(?:www\.)?player\.)?(?:www\.)?vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/)?([0-9]+)#i', $url, $match)) {
		$video_id = $match[1];

		$params = [
			'autoplay'    => $args['autoplay'] ? 1 : 0,
			'muted'       => $args['muted'] ? 1 : 0,
			'loop'        => $args['loop'] ? 1 : 0,
			'playsinline' => $args['playsinline'] ? 1 : 0,
			'title'       => 0,
			'byline'      => 0,
			'portrait'    => 0,
		];

		if (!$args['controls']) {
			$params['background'] = 1;
		}

		$iframe_url = "https://player.vimeo.com/video/{$video_id}?" . http_build_query($params);
		$video_type = 'vimeo';

	} else {
		$attrs = [];

		if ($args['autoplay']) {
			$attrs[] = 'autoplay';
		}

		if ($args['muted']) {
			$attrs[] = 'muted';
		}

		if ($args['loop']) {
			$attrs[] = 'loop';
		}

		if ($args['controls']) {
			$attrs[] = 'controls';
		}

		if ($args['playsinline']) {
			$attrs[] = 'playsinline';
		}

		$poster = '';

		if (!empty($args['poster'])) {
			if (is_numeric($args['poster']) && function_exists('tw_image_link')) {
				$poster_url = tw_image_link($args['poster'], 'full');
			} else {
				$poster_url = $args['poster'];
			}

			if (!empty($poster_url)) {
				$poster = ' poster="' . htmlspecialchars((string) $poster_url, ENT_QUOTES) . '"';
			}
		}

		if (empty($mime)) {
			$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

			$mime_map = [
				'webm' => 'video/webm',
				'ogv'  => 'video/ogg',
				'ogg'  => 'video/ogg',
			];

			$mime = $mime_map[$ext] ?? 'video/mp4';
		}

		return sprintf('<video class="%s"%s%s%s %s><source src="%s" type="%s"></video>', htmlspecialchars($args['class'], ENT_QUOTES), $width, $height, $poster, implode(' ', $attrs), htmlspecialchars($url, ENT_QUOTES), htmlspecialchars($mime, ENT_QUOTES));
	}

	$iframe_style = !$args['controls'] ? ' style="pointer-events: none;"' : '';

	return sprintf('<iframe class="%s %s" src="%s"%s%s%s frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>', htmlspecialchars($args['class'], ENT_QUOTES), htmlspecialchars($video_type, ENT_QUOTES), htmlspecialchars($iframe_url, ENT_QUOTES), $width, $height, $iframe_style);
}