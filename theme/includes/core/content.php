<?php
/**
 * Text Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/**
 * Get a title of a post or term
 *
 * @param WP_Post|WP_Term $object Post object with a title
 * @param int             $length Maximum length of the title
 *
 * @return string
 */
function tw_content_title($object, $length = 0) {

	$title = '';

	if ($object instanceof WP_Post) {

		$title = apply_filters('the_title', $object->post_title, $object->ID);

	} elseif ($object instanceof WP_Term) {

		$title = $object->name;

	}

	if ($title and $length > 0) {
		$title = tw_content_strip($title, $length);
	}

	return $title;

}


/**
 * Get the short description for a given post or text
 *
 * @param bool|string|WP_Post|WP_Term|WP_User $object       Post or text to find and strip the text
 * @param int                                 $length       Required length of the text
 * @param bool|string                         $allowed_tags List of tags separated by "|"
 * @param string                              $find         Symbol to find for proper strip
 * @param bool                                $force_cut    Strip the post excerpt
 *
 * @return bool|string
 */
function tw_content_text($object = false, $length = 250, $allowed_tags = false, $find = ' ', $force_cut = true) {

	$excerpt = false;
	$text = '';

	if ($object === false) {
		$object = get_queried_object();
	}

	if ($object instanceof WP_Post) {

		$text = $object->post_content;
		$excerpt = $object->post_excerpt;

	} elseif ($object instanceof WP_Term) {

		$text = get_term_field('description', $object->term_id);

	} elseif ($object instanceof WP_User) {

		$text = get_user_meta('description', $object->ID);

	} elseif (is_string($object)) {

		$text = $object;

	}

	if (empty($length)) {

		$result = $text;

		if (!empty($excerpt)) {
			$result = $excerpt;
		}

	} else {

		if ($excerpt and mb_strlen($excerpt) > 10) {

			if ($force_cut) {
				$result = tw_content_strip($excerpt, $length, $allowed_tags, $find);
			} else {
				$result = $excerpt;
			}

		} elseif (mb_strpos($text, '<!--more') !== false) {

			$pos = mb_strpos($text, '<!--more');

			if ($force_cut) {
				$result = tw_content_strip(mb_substr($text, 0, $pos), $length, $allowed_tags, $find);
			} else {
				$result = tw_content_strip(mb_substr($text, 0, $pos), $pos, $allowed_tags, $find);
			}

		} else {

			$result = tw_content_strip($text, $length, $allowed_tags, $find);

		}

	}

	return $result;

}


/**
 * Render the ACF link field
 *
 * @param array  $link
 * @param string $class
 *
 * @return string
 */
function tw_content_link($link, $class = 'button') {

	$result = '';

	if (is_array($link) and isset($link['url']) and isset($link['title'])) {

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

		$result = '<a href="' . esc_url($link['url']) . '"' . $class . $target . '>' . $link['title'] . '</a>';

	}

	return $result;

}


/**
 * Get the title for the current page
 *
 * @param string $before          Code to prepend to the title
 * @param string $after           Code to append to the title
 * @param bool   $add_page_number Add a page number to the title
 *
 * @return string
 */
function tw_content_heading($before = '', $after = '', $add_page_number = false) {

	global $post;

	$title = '';

	$object = get_queried_object();

	if ($object instanceof WP_Term) {

		$title = single_term_title('', false);

	} elseif ($object instanceof WP_Post) {

		$title = single_post_title('', false);

	} elseif (is_home()) {

		$title = get_bloginfo('name', 'display');

	} elseif (is_post_type_archive()) {

		$title = post_type_archive_title('', false);

	} elseif (is_404()) {

		$title = __('Page not found', 'twee');

	} elseif (is_search()) {

		$title = sprintf(__('Search results for %s', 'twee'), get_search_query());

	} elseif ($object instanceof WP_User) {

		$title = sprintf(__('Posts of <i>%s</i>', 'twee'), $object->display_name);

	} elseif (is_day()) {

		$title = sprintf(__('Posts for <i>%s</i>', 'twee'), mb_strtolower(get_the_date('d F Y')));

	} elseif (is_month()) {

		$title = sprintf(__('Posts for <i>%s</i>', 'twee'), mb_strtolower(mysql2date('F Y', $post->post_date)));

	} elseif (is_year()) {

		$title = sprintf(__('Posts for <i>%s</i> year', 'twee'), get_the_date('Y'));

	}

	if ($add_page_number and !empty($title) and $page = intval(get_query_var('paged'))) {
		$title .= sprintf(__(' - Page %d', 'twee'), $page);
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
function tw_content_strip($text, $length = 200, $allowed_tags = false, $find = ' ', $dots = '...') {

	if ($allowed_tags) {

		$allowed_tags_list = 'i|em|b|strong|s|del';

		if ($allowed_tags != '+' and mb_strpos($allowed_tags, '+') === 0) {

			$allowed_tags = str_replace('+', '', $allowed_tags);
			$allowed_tags_list = $allowed_tags_list . '|' . $allowed_tags;

		} else {

			$allowed_tags_list = $allowed_tags;

		}

		$allowed_tags_list = '<' . implode('><', explode('|', $allowed_tags_list)) . '>';

	} elseif ($allowed_tags === false) {

		$allowed_tags_list = '<i><em><b><strong><s><del>';

	} else {

		$allowed_tags_list = '';

	}

	$text = strip_tags(strip_shortcodes($text), $allowed_tags_list);

	if ($find and mb_strlen($text) > $length) {

		$pos = mb_strpos($text, $find, $length);

		if ($pos < $length or $pos > ($length + 20)) {
			$pos = $length;
		}

		$text = mb_substr($text, 0, $pos) . $dots;

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

			$text = preg_replace('#<a[^>]*?></a>#is', '', $text);

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
function tw_content_phone($string) {
	return 'tel:' . str_replace([' ', '(', ')', '-', '.'], '', $string);
}


/**
 * Get the formatted date and time for a post
 *
 * @param WP_Post $post   Post object of false to use the current one
 * @param string  $format Date and time format
 *
 * @return string
 */
function tw_content_date($post, $format) {

	$result = '';

	if ($post instanceof WP_Post) {

		if (!$format) {
			$format = get_option('date_format');
		}

		$date = mysql2date($format, $post->post_date);

		$result = apply_filters('get_the_date', $date, $format);

	}

	return $result;

}