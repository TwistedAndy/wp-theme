<?php

/*
Описание: библиотека с общими функциями
Автор: Тониевич Андрей
Версия: 1.9
Дата: 14.09.2016
*/

function tw_wp_title($add_page_number = true) {

	$title = '';

	if (is_404()) {

		$title = __('Page not found', 'wp-theme');

	} elseif (is_search()) {

		$title = sprintf(__('Search results for %s', 'wp-theme'), get_search_query());

	} elseif (is_home() or is_front_page()) {

		$title = get_bloginfo('name', 'display');

	} elseif (is_post_type_archive()) {

		$title = post_type_archive_title('', false);

	} elseif (is_singular()) {

		$title = single_post_title('', false);

	} elseif (is_category() or is_tax()) {

		$title = single_term_title('', false);

	} elseif (is_tag()) {

		$title = sprintf(__('Posts with tag <i>%s</i>', 'wp-theme'), single_term_title('', false));

	} elseif (is_author() and $author = get_queried_object()) {

		$title = sprintf(__('Posts of <i>%s</i>', 'wp-theme'), $author->display_name);

	} elseif (is_day()) {

		$title = sprintf(__('Posts for <i>%s</i>', 'wp-theme'), mb_strtolower(get_the_date('d F Y')));

	} elseif (is_month()) {

		global $post;
		$title = sprintf(__('Posts for <i>%s</i>', 'wp-theme'), mb_strtolower(mysql2date('F Y', $post->post_date)));

	} elseif (is_year()) {

		$title = sprintf(__('Posts for <i>%s</i> year', 'wp-theme'), get_the_date('Y'));

	}

	if ($add_page_number and $title and $page = intval(get_query_var('paged'))) {

		$title .= sprintf(__(' - page %d', 'wp-theme'), $page);

	}

	return apply_filters('wp_title', $title, '', '');

}


function tw_title($post, $length = false) {

	$name = '';

	if (isset($post->post_title)) {

		$name = $post->post_title;

		$name = apply_filters('the_title', $name, $post->ID);

		if ($length) {
			$name = tw_strip_text($name, $length);
		}

	}

	return $name;

}


function tw_breadcrumbs($separator = ' > ') {

	$result = '';

	if (!is_home() or !is_front_page()) {
		$result = '<a href="' . get_site_url() . '" class="home">' . __('Home', 'wp-theme') . '</a>' . $separator;
	}

	$taxonomy = tw_current_taxonomy();

	if (is_single() and $taxonomy) {

		$term = false;

		if ($categories = get_the_terms(get_the_ID(), $taxonomy)) {
			foreach ($categories as $category) {
				$term = $category;
				if (!empty($category->parent) and $category->parent > 0) {
					break;
				}
			}
		}

		if ($term and !empty($term->term_id) and !empty($term->name) and $categories = get_ancestors($term->term_id, $taxonomy)) {
			$categories = array_reverse($categories);
			foreach ($categories as $category) {
				$category = get_term($category, $taxonomy);
				$result .= '<a href="' . get_term_link($category->term_id, $taxonomy) . '">' . $category->name . '</a>' . $separator;
			}
			$result .= '<a href="' . get_term_link($term->term_id, $taxonomy) . '">' . $term->name . '</a>';
		} else {
			$term = tw_current_term(true);
			$result .= '<a href="' . get_term_link($term->term_id, $taxonomy) . '">' . $term->name . '</a>';
		}

		$result .= $separator;

	} elseif (is_page()) {

		$pages = get_ancestors(get_the_ID(), 'page');

		if ($pages) {
			$pages = array_reverse($pages);
			foreach ($pages as $page) {
				$page = get_post($page);
				$result .= '<a href="' . get_page_link($page) . '">' . $page->post_title . '</a>' . $separator;
			}
		}

	} elseif (is_category() or is_tax()) {

		if (is_tax() and $term_object = get_queried_object()) {
			$term_id = $term_object->term_id;
		} else {
			$taxonomy = 'category';
			$term_id = get_query_var('cat');
		}

		if ($term_id and $categories = get_ancestors($term_id, $taxonomy)) {
			$categories = array_reverse($categories);
			foreach ($categories as $category) {
				$category = get_term($category, $taxonomy);
				$result .= '<a href="' . get_term_link($category->term_id, $taxonomy) . '">' . $category->name . '</a>' . $separator;
			}
		}

	}

	$result .= '<span>' . tw_wp_title() . '</span>';

	return $result;

}


function tw_pagination($args = array(), $query = false) {

	$theme_settings = tw_get_setting('pagination');

	if ($theme_settings and is_array($theme_settings) and is_array($args)) {
		$args = wp_parse_args($args, $theme_settings);
	}

	$defaults = array(
		'before' => '<div class="pagination">',
		'after' => '</div>',
		'prev' => '&laquo;',
		'next' => '&raquo;',
		'first' => false,
		'last' => false,
		'pages_text' => '',
		'number' => 10,
		'step' => 10,
		'inactive' => false,
		'dots_left' => '...',
		'dots_right' => '...',
		'type' => 'posts',
		'format' => '',
		'base' => '',
		'add_args' => array(),
		'add_frag' => ''
	);

	$args = wp_parse_args($args, $defaults);

	if ($args['type'] == 'comments') {

		$paged = intval(get_query_var('cpage'));
		$max_page = intval(get_comment_pages_count());

	} elseif ($args['type'] == 'page') {

		global $page, $numpages;

		$paged = intval($page);
		$max_page = intval($numpages);

	} else {

		global $wp_rewrite;

		$url_parts = explode('?', html_entity_decode(get_pagenum_link()));

		if (!empty($url_parts[0])) {

			$pagenum_link = trailingslashit($url_parts[0]) . '%_%';

			if ($wp_rewrite->using_index_permalinks() and !strpos($pagenum_link, 'index.php')) {
				$format = 'index.php/';
			} else {
				$format = '';
			}

			if ($wp_rewrite->using_permalinks()) {
				$format .= user_trailingslashit($wp_rewrite->pagination_base . '/%#%', 'paged');
			} else {
				$format .= '?paged=%#%';
			}

			$args['base'] = $pagenum_link;

			$args['format'] = $format;

			if (!empty($url_parts[1])) {

				$format = explode('?', str_replace('%_%', $args['format'], $args['base']));

				if (isset($format[1])) {
					$format_query = $format[1];
				} else {
					$format_query = '';
				}

				wp_parse_str($format_query, $format_args);

				wp_parse_str($url_parts[1], $url_query_args);

				foreach ($format_args as $format_arg => $format_arg_value) {
					unset($url_query_args[$format_arg]);
				}

				$args['add_args'] = array_merge($args['add_args'], urlencode_deep($url_query_args));

			}

		}

		if (!$query or !($query instanceof WP_Query)) {
			global $wp_query;
			$query = $wp_query;
		}

		$paged = isset($query->query_vars['paged']) ? intval($query->query_vars['paged']) : 1;
		$max_page = isset($query->max_num_pages) ? intval($query->max_num_pages) : 1;

	}

	if ($max_page < 2) {
		return '';
	}

	if ($paged == 0) {
		$paged = 1;
	}

	$result = $args['before'];
	$number = $args['number'] - 1;
	$half_page_start = floor($number / 2);
	$half_page_end = ceil($number / 2);
	$start_page = $paged - $half_page_start;
	$end_page = $paged + $half_page_end;

	if ($start_page < 1) {
		$start_page = 1;
	}

	if (($end_page - $start_page) != $number) {
		$end_page = $start_page + $number;
	}

	if ($end_page > $max_page) {
		$start_page = $max_page - $number;
		$end_page = intval($max_page);
	}

	if ($start_page < 1) {
		$start_page = 1;
	}

	if ($args['pages_text']) {
		$args['pages_text'] = str_replace('{current}', $paged, $args['pages_text']);
		$args['pages_text'] = str_replace('{last}', $max_page, $args['pages_text']);
		$result .= '<span class="pages">' . $args['pages_text'] . '</span>';
	}

	if ($args['first'] !== false and $start_page >= 2 and ($number + 1) < $max_page) {
		$result .= '<a class="prev double" href="' . tw_page_link(1, $args) . '">' . (($args['first'] != 'first') ? $args['first'] : 1) . '</a>';
		if ($args['dots_left'] and $start_page != 2) {
			$result .= '<span class="extend">' . $args['dots_left'] . '</span>';
		}
	}

	if ($args['prev'] !== false) {
		if ($paged != 1) {
			$result .= '<a class="prev" href="' . tw_page_link(($paged - 1), $args) . '">' . $args['prev'] . '</a>';
		} elseif ($args['inactive']) {
			$result .= '<span class="prev">' . $args['prev'] . '</span>';
		}
	}

	for ($i = $start_page; $i <= $end_page; $i++) {
		if ($i == $paged) {
			$result .= '<span class="current">' . $i . '</span>';
		} else {
			$result .= '<a href="' . tw_page_link($i, $args) . '">' . $i . '</a>';
		}
	}

	if ($args['step'] and $end_page < $max_page) {
		$dd = 0;
		for ($i = $end_page + 1; $i <= $max_page; $i++) {
			if ($i % $args['step'] == 0 && $i !== $args['number']) {
				if (++$dd == 1) {
					$result .= '<span class="extend">' . $args['dots_right'] . '</span>';
				}
				$result .= '<a href="' . tw_page_link($i, $args) . '">' . $i . '</a>';
			}
		}
	}

	if ($args['next'] !== false) {
		if ($paged != $end_page) {
			$result .= '<a class="next" href="' . tw_page_link(($paged + 1), $args) . '">' . $args['next'] . '</a>';
		} elseif ($args['inactive']) {
			$result .= '<span class="next">' . $args['next'] . '</span>';
		}
	}

	if ($args['last'] !== false and $end_page < $max_page) {
		if ($args['dots_right'] and $end_page != ($max_page - 1)) {
			$result .= '<span class="extend">' . $args['dots_right'] . '</span>';
		}
		$result .= '<a class="next double" href="' . tw_page_link($max_page, $args) . '">' . (($args['last'] != 'last') ? $args['last'] : $max_page) . '</a>';
	}

	$result .= $args['after'];

	return $result;

}


function tw_page_link($page_number, $args = array()) {

	if (is_array($args) and isset($args['type'])) {
		$type = $args['type'];
	} elseif (is_string($args)) {
		$type = $args;
	} else {
		$type = false;
	}

	if ($type == 'comments') {

		$link = get_comments_pagenum_link($page_number);

	} elseif ($type == 'page') {

		$link = str_replace(array('<a href="', '">'), '', _wp_link_page($page_number));
		$link = apply_filters('wp_link_pages_link', $link, $page_number);

	} else {

		if (is_array($args) and !empty($args['base']) and !empty($args['format'])) {

			$link = str_replace('%_%', ($page_number == 1 ? '' : $args['format']), $args['base']);
			$link = str_replace('%#%', $page_number, $link);

			if (!empty($args['add_args'])) {
				$link = add_query_arg($args['add_args'], $link);
			}

			$link .= $args['add_fragment'];
			$link = apply_filters('paginate_links', $link);

		} else {

			$link = get_pagenum_link($page_number);

		}

	}

	return $link;

}


function tw_strip_text($text, $length, $allowed_tags = false, $find = ' ', $dots = '...') {

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


function tw_text($post = false, $length = 250, $allowed_tags = false, $find = ' ', $force_cut = true) {

	if ($post === false) {
		$post = get_post();
	}

	if ($post and isset($post->post_content)) {

		$text = $post->post_content;

		if (isset($post->post_excerpt)) {
			$excerpt = $post->post_excerpt;
		} else {
			$excerpt = false;
		}

	} elseif ($post and is_string($post)) {

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

		return false;

	}

}


function tw_create_thumb($image_url, $size) {

	global $_wp_additional_image_sizes;

	$result = false;

	$position = mb_strrpos($image_url, '/');

	if ($position < mb_strlen($image_url)) {

		$filename = mb_strtolower(mb_substr($image_url, $position + 1));

		if (preg_match('#(.*?)\.(gif|jpg|jpeg|png|bmp)$#is', $filename, $matches)) {

			if (is_array($size) or (is_string($size) and $size != 'full')) {

				$crop = true;
				$width = 0;
				$height = 0;

				if (is_string($size) and $size and !empty($_wp_additional_image_sizes[$size])) {
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
					$editor = wp_get_image_editor($image_url);
					if (!is_wp_error($editor)) {
						$editor->resize($width, $height, $crop);
						$editor->save(get_template_directory() . $filename);
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


function tw_thumb($post = false, $size = false, $before = '', $after = '', $atts = array(), $thumb_only = false) {

	global $_wp_additional_image_sizes;

	$thumb = '';
	$link_href = false;
	$link_image_size = false;

	if ($post == false) {
		$post = get_post();
	}

	if (!empty($atts['link'])) {

		if ($atts['link'] == 'url') {

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

	if (has_post_thumbnail($post->ID)) {

		if ($link_image_size) {

			$thumb = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), $link_image_size);

			if (!empty($thumb[0])) {
				$link_href = $thumb[0];
			}

		}

		$thumb = get_the_post_thumbnail($post->ID, $size, $atts);

	} elseif (!$thumb_only) {

		$image = tw_create_thumb(tw_find_image($post->post_content), $size);

		if ($image) {

			if ($link_image_size and !$link_href) {
				$link_href = tw_create_thumb($image, $link_image_size);
			}

			$thumb = '<img src="' . $image . '" alt="' . $post->post_title . '"' . ((isset($atts['class'])) ? ' class="' . $atts['class'] . '"' : '') . ' />';

		}

	}

	if ($link_href) {
		$before = $before . '<a href="' . $link_href . '"' . $class . '>';
		$after = '</a>' . $after;
	}

	if ($thumb) {
		return $before . $thumb . $after;
	} else {
		return $thumb;
	}

}


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


function tw_get_template_part($name, $variables = array()) {

	if (is_array($variables) and $variables) {
		extract($variables);
	}

	$filename = get_template_directory() . '/parts/' . $name . '.php';

	if (is_file($filename)) {
		include($filename);
	}

}


function tw_get_rating($post_id) {

	$rating_sum = get_post_meta($post_id, 'rating_sum', true);
	$rating_votes = get_post_meta($post_id, 'rating_votes', true);

	if ($rating_votes == 0) {
		$rating_votes = 1;
	}

	return array(
		'rating' => round($rating_sum / $rating_votes, 1),
		'votes' => intval($rating_votes)
	);

}


function tw_get_views($post_id) {

	$count_key = 'post_views_count';

	$count = get_post_meta($post_id, $count_key, true);

	if (!$count) {
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, 0);
		return 0;
	}

	return $count;

}


function tw_set_views($post_id) {

	$count_key = 'post_views_count';

	$count = get_post_meta($post_id, $count_key, true);

	if ($count = intval($count)) {
		$count++;
		update_post_meta($post_id, $count_key, $count);
	} else {
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, 1);
	}

}


