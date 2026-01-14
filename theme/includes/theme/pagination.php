<?php
/**
 * Pagination Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.3
 */

/**
 * Build the pagination for a given WP_Query object
 *
 * @param array         $args
 * @param bool|WP_Query $query WP_Query object. Leave empty to use the current one
 *
 * @return string
 */
function tw_pagination(array $args = [], $query = false): string
{
	$defaults = [
		'before'     => '<div role="navigation" aria-label="' . esc_attr__('Pages', 'twee') . '" class="pagination_box">',
		'after'      => '</div>',
		'prev'       => '',
		'next'       => '',
		'first'      => false,
		'last'       => false,
		'pages_text' => '',
		'number'     => 10,
		'step'       => 10,
		'inactive'   => false,
		'dots_left'  => '...',
		'dots_right' => '...',
		'type'       => 'posts',
		'format'     => '',
		'base'       => '',
		'add_args'   => [],
		'add_frag'   => '',
		'url'        => false,
		'page'       => false,
		'max_page'   => false
	];

	$args = wp_parse_args($args, $defaults);

	$paged = tw_page_number($args, $query);

	$max_page = tw_page_total($args, $query);

	if ($max_page < 2) {
		return '';
	}

	if ($args['type'] != 'comments' and $args['type'] != 'page') {

		global $wp_rewrite;

		if (empty($args['url'])) {
			$args['url'] = html_entity_decode(get_pagenum_link());
		}

		$url_parts = explode('?', $args['url']);

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
		$result .= '<a aria-label="' . esc_attr__('First Page', 'twee') . '" class="prev first" href="' . tw_page_link(1, $args) . '">' . (($args['first'] != 'first') ? $args['first'] : 1) . '</a>';
		if ($args['dots_left'] and $start_page != 2) {
			$result .= '<span class="extend">' . $args['dots_left'] . '</span>';
		}
	}

	if ($args['prev'] !== false) {
		if ($paged != 1) {
			$result .= '<a aria-label="' . esc_attr__('Previous Page', 'twee') . '" class="prev" href="' . tw_page_link(($paged - 1), $args) . '">' . $args['prev'] . '</a>';
		} elseif ($args['inactive']) {
			$result .= '<span class="prev">' . $args['prev'] . '</span>';
		}
	}

	for ($i = $start_page; $i <= $end_page; $i++) {
		if ($i == $paged) {
			$result .= '<span aria-current="page" class="current">' . $i . '</span>';
		} else {
			$result .= '<a aria-label="' . esc_attr(sprintf(__('Page %d', 'twee'), $i)) . '" href="' . tw_page_link($i, $args) . '">' . $i . '</a>';
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
			$result .= '<a aria-label="' . esc_attr__('Next Page', 'twee') . '" class="next" href="' . tw_page_link(($paged + 1), $args) . '">' . $args['next'] . '</a>';
		} elseif ($args['inactive']) {
			$result .= '<span class="next">' . $args['next'] . '</span>';
		}
	}

	if ($args['last'] !== false and $end_page < $max_page) {
		if ($args['dots_right'] and $end_page != ($max_page - 1)) {
			$result .= '<span class="extend">' . $args['dots_right'] . '</span>';
		}
		$result .= '<a aria-label="' . esc_attr__('Last Page', 'twee') . '" class="next last" href="' . tw_page_link($max_page, $args) . '">' . (($args['last'] != 'last') ? $args['last'] : $max_page) . '</a>';
	}

	$result .= $args['after'];

	return $result;
}


/**
 * Build a link for a given page number
 *
 * @param int   $page_number Page number
 * @param array $args
 *
 * @return string
 */
function tw_page_link($page_number, $args = []): string
{
	if (is_array($args) and isset($args['type'])) {
		$type = $args['type'];
	} else {
		$type = false;
	}

	if ($type == 'comments') {
		$link = get_comments_pagenum_link($page_number);
	} elseif ($type == 'page') {
		$link = str_replace(['<a href="', '">'], '', _wp_link_page($page_number));
		$link = apply_filters('wp_link_pages_link', $link, $page_number);
	} elseif (is_array($args) and !empty($args['base']) and !empty($args['format'])) {
		$link = str_replace(['%_%', '%#%'], [($page_number == 1 ? '' : $args['format']), $page_number], $args['base']);

		if (!empty($args['add_args'])) {
			$link = add_query_arg($args['add_args'], $link);
		}

		$link .= $args['add_frag'];
		$link = apply_filters('paginate_links', $link);
	} else {
		$link = get_pagenum_link($page_number);
	}

	return $link;
}


/**
 * Get current page number
 *
 * @param array         $args  Array with configuration
 * @param bool|WP_Query $query Custom WordPress query
 *
 * @return int
 */
function tw_page_number($args = [], $query = false): int
{
	if (!empty($args['page'])) {
		$page_number = $args['page'];
	} elseif (!empty($args['type']) and $args['type'] == 'comments') {
		$page_number = get_query_var('cpage');
	} elseif (!empty($args['type']) and $args['type'] == 'page') {
		global $page;
		$page_number = $page;
	} else {
		if (!$query or !($query instanceof WP_Query)) {
			global $wp_query;
			$query = $wp_query;
		}

		$page_number = $query->get('paged', 1);
	}

	$page_number = (int) $page_number;

	if ($page_number < 1) {
		$page_number = 1;
	}

	return $page_number;
}


/**
 * Get total pages count
 *
 * @param array         $args  Array with configuration
 * @param bool|WP_Query $query Custom WordPress query
 *
 * @return int
 */
function tw_page_total($args = [], $query = false): int
{
	if (!empty($args['max_page'])) {
		$max_page = $args['max_page'];
	} elseif (!empty($args['type']) and $args['type'] == 'comments') {
		$max_page = get_comment_pages_count();
	} elseif (!empty($args['type']) and $args['type'] == 'page') {
		global $numpages;
		$max_page = $numpages;
	} else {
		if (!$query or !($query instanceof WP_Query)) {
			global $wp_query;
			$query = $wp_query;
		}

		$max_page = $query->max_num_pages ?? 1;
	}

	return (int) $max_page;
}