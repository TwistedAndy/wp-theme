<?php
/**
 * Title, text and date processing library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


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

function tw_strip_text($text, $length = 200, $allowed_tags = false, $find = ' ', $dots = '...') {

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

	if ($allowed_tags_list) {
		$text = force_balance_tags($text);
	}

	return $text;

}


/**
 * Get the title of a given post
 *
 * @param     $post       WP_Post Post object with a title
 * @param int $length     Maximum length of the title
 *
 * @return string
 */

function tw_title($post, $length = 0) {

	$title = '';

	if (!empty($post->post_title)) {

		$title = $post->post_title;

		$title = apply_filters('the_title', $title, $post->ID);

	} elseif (!empty($post->name)) {

		$title = $post->name;

	}

	if ($title and $length) {
		$title = tw_strip_text($title, $length);
	}

	return $title;

}


/**
 * Get the short description for a given post or text
 *
 * @param bool|string|WP_Post $post         Post or text to find and strip the text
 * @param int                 $length       Required length of the text
 * @param bool|string         $allowed_tags List of tags separated by "|"
 * @param string              $find         Symbol to find for proper strip
 * @param bool                $force_cut    Strip the post excerpt
 *
 * @return bool|string
 */

function tw_text($post = false, $length = 250, $allowed_tags = false, $find = ' ', $force_cut = true) {

	if ($post === false) {
		$post = get_post();
	}

	if (!empty($post->post_content)) {

		$text = $post->post_content;

		if (isset($post->post_excerpt)) {
			$excerpt = $post->post_excerpt;
		} else {
			$excerpt = false;
		}

	} elseif (is_string($post) and $post) {

		$text = $post;
		$excerpt = false;

	} else {

		$text = get_the_content();
		$excerpt = get_the_excerpt();

	}

	if ($excerpt and mb_strlen($excerpt) > 0) {

		if ($force_cut) {
			$result = tw_strip_text($excerpt, $length, $allowed_tags, $find);
		} else {
			$result = $excerpt;
		}

	} elseif (mb_strpos($text, '<!--more') !== false) {

		$pos = mb_strpos($text, '<!--more');

		if ($force_cut) {
			$result = tw_strip_text(mb_substr($text, 0, $pos), $length, $allowed_tags, $find);
		} else {
			$result = tw_strip_text(mb_substr($text, 0, $pos), $pos, $allowed_tags, $find);
		}

	} else {

		$result = tw_strip_text($text, $length, $allowed_tags, $find);

	}

	return $result;

}


/**
 * Get the formatted phone number for a link href attribute
 *
 * @param string $string String with phone number
 *
 * @return string
 */

function tw_phone($string) {

	$string = 'tel:' . str_replace(array(' ', '(', ')', '-'), '', $string);

	return $string;

}


/**
 * Get the date and time for a given post
 *
 * @param bool|WP_Post $post   Post object of false to use the current one
 * @param string       $format Date and time format
 *
 * @return string
 */

function tw_date($post = false, $format = '') {

	if ($post == false) {
		$post = get_post();
	}

	if (!$format) {
		$format = get_option('date_format');
	}

	$date = mysql2date($format, $post->post_date);

	return apply_filters('get_the_date', $date, $format);

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

function tw_wp_title($before = '', $after = '', $add_page_number = false) {

	global $post;

	$title = '';

	if (is_category() or is_tax()) {

		$title = single_term_title('', false);

	} elseif (is_singular()) {

		$title = single_post_title('', false);

	} elseif (is_home() or is_front_page()) {

		$title = get_bloginfo('name', 'display');

	} elseif (is_post_type_archive()) {

		$title = post_type_archive_title('', false);

	} elseif (is_404()) {

		$title = __('Page not found', 'wp-theme');

	} elseif (is_search()) {

		$title = sprintf(__('Search results for %s', 'wp-theme'), get_search_query());

	} elseif (is_tag()) {

		$title = sprintf(__('Posts with tag <i>%s</i>', 'wp-theme'), single_term_title('', false));

	} elseif (is_author() and $author = get_queried_object()) {

		$title = sprintf(__('Posts of <i>%s</i>', 'wp-theme'), $author->display_name);

	} elseif (is_day()) {

		$title = sprintf(__('Posts for <i>%s</i>', 'wp-theme'), mb_strtolower(get_the_date('d F Y')));

	} elseif (is_month()) {

		$title = sprintf(__('Posts for <i>%s</i>', 'wp-theme'), mb_strtolower(mysql2date('F Y', $post->post_date)));

	} elseif (is_year()) {

		$title = sprintf(__('Posts for <i>%s</i> year', 'wp-theme'), get_the_date('Y'));

	}

	if ($add_page_number and !empty($title) and $page = intval(get_query_var('paged'))) {
		$title .= sprintf(__(' - page %d', 'wp-theme'), $page);
	}

	if (!empty($title)) {
		$title = $before . $title . $after;
	}

	return $title;

}


/**
 * Get the text for a "Page not found" message
 *
 * @return string
 */

function tw_not_found_text() {

	if (is_category()) {

		$result = __('There are no posts in this category', 'wp-theme');

	} elseif (is_tax('product_cat') or is_tax('product_tag')) {

		$result = __('No products were found matching your selection.', 'woocommerce');

	} elseif (is_page() or is_single()) {

		$result = __('The requested post is not found', 'wp-theme');

	} elseif (is_tag()) {

		$result = sprintf(__('There are no posts with tag <i>%s</i>', 'wp-theme'), single_term_title('', false));

	} elseif (is_day()) {

		$result = __('There are no posts for requested day', 'wp-theme');

	} elseif (is_month()) {

		$result = __('There are no posts for requested month', 'wp-theme');

	} elseif (is_year()) {

		$result = __('There are no posts for requested year', 'wp-theme');

	} elseif (is_author()) {

		$result = __('There are no posts by this author', 'wp-theme');

	} elseif (is_404()) {

		$result = __('Sorry, but there is nothing to show by requested address', 'wp-theme');

	} elseif (is_search()) {

		$result = sprintf(__('There are no information matching the query <i>%s</i>', 'wp-theme'), get_search_query());

	} else {

		$result = __('There are no posts to show', 'wp-theme');

	}

	return $result;

}