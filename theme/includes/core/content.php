<?php
/**
 * Text Processing Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
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
 * @param string $hidden
 *
 * @return string
 */
function tw_content_link($link, $class = 'button', $hidden = '') {

	$result = '';

	if (is_array($link) and !empty($link['url']) and isset($link['title'])) {

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

		$result = '<a href="' . esc_url($link['url']) . '"' . $class . $target . '>' . $link['title'] . $hidden . '</a>';

	}

	return $result;

}


/**
 * Get the title for the current page
 *
 * @param WP_Query|null $query
 * @param string        $before          Code to prepend to the title
 * @param string        $after           Code to append to the title
 * @param bool          $add_page_number Add a page number to the title
 *
 * @return string
 */
function tw_content_heading($query = null, $before = '', $after = '', $add_page_number = false) {

	$title = '';

	if (empty($query)) {
		global $wp_query;
		$query = $wp_query;
	}

	if (!($query instanceof WP_Query)) {
		return $title;
	}

	$object = $query->get_queried_object();

	if ($query->is_home()) {

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

		preg_match('#(.+)</[^>]+$#is', $text, $matches);

		if (!empty($matches[1])) {
			$text = $matches[1];
		}

		$text = $text . $dots;

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


/**
 * Get the reading time
 *
 * @param WP_Post $post
 * @param string  $label
 *
 * @return string
 */
function tw_content_time($post, $label = ' min read') {

	$result = '';

	if ($post instanceof WP_Post) {

		$word_count = str_word_count(strip_tags($post->post_content));

		$time = ceil($word_count / 200);

		if ($time < 3) {
			$time = 3;
		}

		$result = $time . $label;

	}

	return $result;

}