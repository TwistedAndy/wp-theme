<?php

/*
Описание: библиотека с общими функциями
Автор: Тониевич Андрей
Версия: 1.7
Дата: 05.03.2016
*/

function tw_wp_title($add_page_number = false) {

	$result = '';

	if (is_404()) {

		$result = 'Произошла ошибка';

	} elseif (is_search()) {

		$result = 'Результаты поиска по запросу: ' . get_search_query();

	} elseif (is_front_page()) {

		$result = get_bloginfo('name', 'display');

	} elseif (is_post_type_archive()) {

		$result = post_type_archive_title('', false);

	} elseif (is_home() || is_singular()) {

		$result = single_post_title('', false);

	} elseif (is_category() or is_tax()) {

		$result = single_term_title('', false);

	} elseif (is_tag()) {

		$result = single_term_title('Записи с тегом: ', false);

	} elseif (is_author() and $author = get_queried_object()) {

		$result = 'Записи пользователя ' . $author->display_name;

	} elseif (is_year()) {

		$result = 'Записи за ' . get_the_date('Y') . ' год';

	} elseif (is_month()) {

		global $post;

		$result = 'Записи за ' . mb_strtolower(mb_strtolower(mysql2date('F Y', $post->post_date))) . ' года';

	} elseif (is_day()) {

		$result = 'Записи за ' . mb_strtolower(get_the_date('d F Y')) . ' года';

	}

	if ($add_page_number and $result and $page = intval(get_query_var('paged'))) {
		$result .= ' - ' . $page . ' страница';
	}

	return $result;

}


function tw_title($item, $len = false) {

	$name = '';

	if (isset($item->post_title)) {

		$name = $item->post_title;

		$name = apply_filters('the_title', $name, $item->ID);

		if ($len) $name = tw_strip($name, $len);

	}

	return $name;

}


function tw_breadcrumbs($separator = ' > ') {

	if (!is_home()) {
		echo '<a href="' . get_option('home') . '" class="home">Главная</a>';
		echo $separator;
	}

	$taxonomy = tw_current_taxonomy();

	if (is_single() and $taxonomy) {

		$term = false;

		if ($categories = get_the_terms(get_the_ID(), $taxonomy)) {
			foreach ($categories as $category) {
				$term = $category;
				if (!empty($category->parent) and $category->parent > 0) break;
			}
		}

		if ($term and !empty($term->term_id) and !empty($term->name) and $categories = get_ancestors($term->term_id, $taxonomy)) {
			$categories = array_reverse($categories);
			foreach ($categories as $category) {
				$category = get_term($category, $taxonomy);
				echo '<a href="' . get_term_link($category->term_id, $taxonomy) . '">' . $category->name . '</a>' . $separator;
			}
			echo '<a href="' . get_term_link($term->term_id, $taxonomy) . '">' . $term->name . '</a>';
		} else {
			$term = tw_current_term(true);
			echo '<a href="' . get_term_link($term->term_id, $taxonomy) . '">' . $term->name . '</a>';
		}

		echo $separator;

	} elseif (is_page()) {

		$pages = get_ancestors(get_the_ID(), 'page');

		if ($pages) {
			$pages = array_reverse($pages);
			foreach ($pages as $page) {
				$page = get_post($page);
				echo '<a href="' . get_page_link($page) . '">' . $page->post_title . '</a>' . $separator;
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
				echo '<a href="' . get_term_link($category->term_id, $taxonomy) . '">' . $category->name . '</a>' . $separator;
			}
		}

	}

	return '<span>' . tw_wp_title() . '</span>';

}


function tw_navigation($args = array(), $query = false) {

	if (tw_settings('navigation') and is_array($args)) {
		$args = array_merge(tw_settings('navigation'), $args);
	}

	$defaults = array(
		'before'	=> '<div class="pagination">',
		'after'		=> '</div>',
		'prev'		=> '&laquo;',
		'next'		=> '&raquo;',
		'first'		=> false,
		'last'		=> false,
		'pages_text'=> '',
		'number'	=> 10,
		'step'		=> 10,
		'inactive'	=> false,
		'dots_left' => '...',
		'dots_right'=> '...',
		'type'		=> 'posts'
	);

	foreach ($defaults as $key => $value) {
		if (isset($args[$key])) $$key = $args[$key]; else $$key = $value;
	}

	if ($type == 'comments') {
		$paged = intval(get_query_var('cpage'));
		$max_page = intval(get_comment_pages_count());
	} elseif ($type == 'page') {
		global $page, $numpages;
		$paged = intval($page);
		$max_page = intval($numpages);
	} else {
		if (!$query or !($query instanceof WP_Query)) {
			global $wp_query;
			$query = $wp_query;
		}
		$paged = isset($query->query_vars['paged']) ? intval($query->query_vars['paged']) : 1;
		$max_page = isset($query->max_num_pages ) ? intval($query->max_num_pages) : 1;
	}

	if ($max_page < 2) return '';
	if ($paged == 0) $paged = 1;

	$out = $before;
	$number = $number - 1;
	$half_page_start = floor($number/2);
	$half_page_end = ceil($number/2);
	$start_page = $paged - $half_page_start;
	$end_page = $paged + $half_page_end;

	if ($start_page <= 0) $start_page = 1;

	if (($end_page - $start_page) != $number) $end_page = $start_page + $number;

	if ($end_page > $max_page) {
		$start_page = $max_page - $number;
		$end_page = intval($max_page);
	}

	if ($start_page <= 0) $start_page = 1;

	if ($pages_text) {
		$pages_text = str_replace('{current}', $paged, $pages_text);
		$pages_text = str_replace('{last}', $max_page, $pages_text);
		$out .= '<span class="pages">' . $pages_text . '</span>';
	}

	if ($first and $start_page >= 2 and ($number + 1) < $max_page) {
		$out .= '<a class="prev double" href="' . tw_page_link(1, $type) . '">' . (($first != 'first') ? $first : 1) . '</a>';
		if ($dots_left and $start_page != 2) $out .= '<span class="extend">' . $dots_left . '</span>';
	}

	if ($paged != 1) {
		$out .= '<a class="prev" href="' . tw_page_link(($paged-1), $type) . '">' . $prev . '</a>';
	} elseif ($inactive) {
		$out .= '<span class="prev">' . $prev .'</span>';
	}

	for ($i = $start_page; $i <= $end_page; $i++) {
		if ($i == $paged) {
			$out .= '<span class="current">' . $i . '</span>';
		} else {
			$out .= '<a href="' . tw_page_link($i, $type) . '">' . $i . '</a>';
		}
	}

	if ($step and $end_page < $max_page){
		for ($i = $end_page + 1; $i <= $max_page; $i++) {
			if ($i % $step == 0 && $i !== $num_pages) {
				if (++$dd == 1) $out .= '<span class="extend">' . $dots_right . '</span>';
				$out .= '<a href="' . tw_page_link($i, $type) . '">' . $i . '</a>';
			}
		}
	}

	if ($paged != $end_page) {
		$out .= '<a class="next" href="' . tw_page_link(($paged+1), $type) . '">' . $next . '</a>';
	} elseif ($inactive) {
		$out .= '<span class="next">' . $next . '</span>';
	}

	if ($last and $end_page < $max_page) {
		if ($dots_right and $end_page != ($max_page-1)) $out.= '<span class="extend">' . $dots_right . '</span>';
		$out .= '<a class="next double" href="' . tw_page_link($max_page, $type) . '">' . (($last != 'last') ? $last : $max_page) . '</a>';
	}

	$out .= $after;

	return $out;

}


function tw_page_link($page, $type = false) {

	if ($type == 'comments') {
		$link = get_comments_pagenum_link($page);
	} elseif ($type == 'page') {
		$link = str_replace(array('<a href="', '">'), '', _wp_link_page($page));
		$link = apply_filters('wp_link_pages_link', $link, $page);
	} else {
		$link = get_pagenum_link($page);
	}

	return $link;

}


function tw_strip($text, $len, $allowed_tags = false, $find = ' ', $dots = '...') {

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

	if ($find and mb_strlen($text) > $len) {
		$pos = mb_strpos($text, $find, $len);
		if ($pos < $len or $pos > ($len + 20)) $pos = $len;
		$text = mb_substr($text, 0, $pos) . $dots;
	} else {
		$pos = $len;
		$text = mb_substr($text, 0, $pos);
	}

	if (mb_strpos($allowed_tags_list, '<a>') !== false) {
		$link_start =  mb_strrpos($text, '<a');
		if ($link_start !== false) {
			$link_end = mb_strpos($text, '</a>', $link_start);
			if ($link_end === false) {
				$text = mb_substr($text, 0, $link_start) . $dots;
			}
		}
		$text = preg_replace('#<a[^>]*?></a>#is', '', $text);
	}

	if ($allowed_tags_list) $text = force_balance_tags($text);

	return $text;

}


function tw_text($item = false, $len = 250, $allowed_tags = false, $find = ' ', $force_cut = true) {

	if ($item and isset($item->post_content) and isset($item->post_excerpt)) {
		$text = $item->post_content;
		$excerpt = $item->post_excerpt;
	} else {
		$text = get_the_content();
		$excerpt = get_the_excerpt();
	}

	if ($excerpt and mb_strlen($excerpt) > 0) {

		if ($force_cut) {
			$result = tw_strip($excerpt, $len, $allowed_tags, $find);
		} else {
			$result = $excerpt;
		}

	} elseif (mb_strpos($text, '<!--more') !== false) {

		$pos = mb_strpos($text, '<!--more');

		if ($force_cut) {
			$result = tw_strip(mb_substr($text, 0, $pos), $len, $allowed_tags, $find);
		} else {
			$result = tw_strip(mb_substr($text, 0, $pos), $pos, $allowed_tags, $find);
		}

	} else {

		$result = tw_strip($text, $len, $allowed_tags, $find);

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


function tw_get_thumb($image_url, $size) {

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

				if (is_string($size) and $size and isset($_wp_additional_image_sizes[$size])) {
					$width = $_wp_additional_image_sizes[$size]['width'];
					$height = $_wp_additional_image_sizes[$size]['height'];
					$crop = $_wp_additional_image_sizes[$size]['crop'];
				} elseif (is_array($size) and $size) {
					if (isset($size[0])) $width = $size[0];
					if (isset($size[1])) $height = $size[1];
					if (isset($size[2])) $crop = $size[2];
				} else {
					$width = $_wp_additional_image_sizes['thumbnail']['width'];
					$height = $_wp_additional_image_sizes['thumbnail']['height'];
					$crop = $_wp_additional_image_sizes['thumbnail']['crop'];
				}

				$width = intval($width);
				$height = intval($height);

				$filename =  '/library/cache/' . $matches[1] . '-' . $width . '-' . $height . '.' . $matches[2];

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


function tw_thumb($item, $size = false, $before = '', $after = '', $atts = array(), $thumb_only = false) {

	global $_wp_additional_image_sizes;

	$result = '';
	$src = false;

	if (!$item) $item = get_post();

	if (!isset($atts['link'])) {

		$link = false;

	} else {

		if ($atts['link'] == 'url') {
			$src = get_permalink($item->ID);
		}

		if (isset($_wp_additional_image_sizes[$atts['link']])) {
			$link = $atts['link'];
		} else {
			$link = 'full';
		}

		unset($atts['link']);

	}

	if (!isset($atts['link_class'])) {
		$class = '';
	} else {
		$class = ' class="' . $atts['link_class'] . '"';
		unset($atts['link_class']);
	}

	if (!$size or (is_string($size) and empty($_wp_additional_image_sizes[$size]) and !in_array($size, array('thumbnail', 'medium', 'large', 'full')))) {
		$size = 'thumbnail';
	}

	if (has_post_thumbnail($item->ID)) {

		if ($link and !$src and $thumb = wp_get_attachment_image_src(get_post_thumbnail_id($item->ID), $link) and isset($thumb[0])) {
			$src = $thumb[0];
		}

		$result = get_the_post_thumbnail($item->ID, $size, $atts);

	} elseif (!$thumb_only and $image = tw_get_thumb(tw_find_image($item->post_content), $size)) {

		if ($link and !$src) {
			$src = tw_get_thumb($image, $link);
		}

		$result = '<img src="' . $image . '" alt="' . $item->post_title . '"' . ((isset($atts['class'])) ? ' class="' . $atts['class'] . '"' : '') . ' />';

	}

	if ($src) {
		$before = $before . '<a href="' . $src . '"' . $class . '>';
		$after = '</a>' . $after;
	}

	if ($result) {
		return $before . $result . $after;
	} else {
		return $result;
	}

}


function tw_date($item, $format = '') {

	if (!$format) $format = get_option('date_format');

	$date = mysql2date($format, $item->post_date);

	return apply_filters('get_the_date', $date, $format);

}


function tw_none() {

	if (is_category()) {
		echo 'В данной рубрике записи не обнаружены';
	} elseif (is_page() or is_single()) {
		echo 'Запись не обнаружена';
	} elseif (is_tag()) {
		echo 'С данным тегом записи не обнаружены';
	} elseif (is_day()) {
		echo 'За этот день записи не обнаружены';
	} elseif (is_month()) {
		echo 'За этот месяц записи не обнаружены';
	} elseif (is_year()) {
		echo 'За этот год записи не обнаружены';
	} elseif (is_author()) {
		echo 'У этого автора записи отсутствуют';
	} elseif (is_404()) {
		echo 'По данному адресу записи не обнаружены';
	} elseif (is_search()) {
		echo 'По запросу <i>' . get_search_query() . '</i> ничего не найдено';
	} else {
		echo 'Записи не обнаружены';
	}

}


function tw_get_views($post_id) {

	$count_key = 'post_views_count';

	$count = get_post_meta($post_id, $count_key, true);

	if (!$count) {
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '0');
		return '0';
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
		add_post_meta($post_id, $count_key, '0');
	}

}


function tw_get_rating($post_id) {

	$rating_value = get_post_meta($post_id, 'rating_value', true);
	$rating_votes = get_post_meta($post_id, 'rating_votes', true);

	if ($rating_votes == 0) $rating_votes = 1;

	return array(
		'rating' => round($rating_value/$rating_votes),
		'votes' => intval($rating_votes)
	);

}