<?php

/*
Описание: библиотека с общими функциями
Автор: Тониевич Андрей
Версия: 1.5
Дата: 18.01.2015
*/

function tw_wp_title() {
	
	if (is_category()) {
		single_cat_title('', true);
	} elseif (is_page() or is_single()) {
		the_title();
	} elseif (is_tag()) {
		single_tag_title();
	} elseif (is_day()) {
		echo 'Архив за ';
		the_time('d F Y');
	} elseif (is_month()) {
		global $post;
		echo 'Архив за ' . mb_strtolower(mysql2date('F Y', $post->post_date));
	} elseif (is_year()) {
		echo "Архив за ";
		the_time('Y');
	} elseif (is_author()) {
		echo "Архив";
	} elseif (is_404()) {
		echo "Произошла ошибка";
	} elseif (isset($_GET['paged']) && !empty($_GET['paged'])) {
		echo "Архив блога";
	} elseif (is_search()) {
		echo "Результаты поиска по запросу: <i>" . get_search_query() . "</i>";
	}
	
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
	
	if (is_single()) {
		$cat = false;
		$categories = get_the_terms(get_the_ID(), 'category');
		if ($categories) {
			foreach ($categories as $category) {
				$cat = $category;
				if ($category->parent > 0) break;
			}
		}
		if ($cat and $categories = get_ancestors($cat->term_id, 'category')) {
			$categories = array_reverse($categories);
			foreach ($categories as $category) {
				$category = get_category($category);
				echo '<a href="' . get_category_link($category->term_id) . '">' . $category->name . '</a>' . $separator;
			}
			echo '<a href="' . get_category_link($cat->term_id) . '">' . $cat->name . '</a>';
		} else {
			$cat = tw_current_category(true);
			echo '<a href="' . get_category_link($cat->term_id) . '">' . $cat->name . '</a>';
		}
		echo $separator;
	} elseif (is_page()) {
		$pages = get_ancestors(get_the_ID(), 'page');
		if ($pages) {
			$pages = array_reverse($pages);
			foreach ($pages as $page) {
				$page = get_page($page);
				echo '<a href="' . get_page_link($page->ID) . '">' . $page->post_title . '</a>' . $separator;				
			}
		}
	} elseif (is_category()) {
		if ($categories = get_ancestors(get_query_var('cat'), 'category')) {
			$categories = array_reverse($categories);
			foreach ($categories as $category) {
				$category = get_category($category);
				echo '<a href="' . get_category_link($category->cat_ID) . '">' . $category->cat_name . '</a>' . $separator;
			}
		}
	}
	
	echo '<span>';
	tw_wp_title();
	echo '</span>';

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
		$paged = intval($query->query_vars['paged']);  
		$max_page = intval($query->max_num_pages);  
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
 
	if ($step && $end_page < $max_page){  
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
	
	global $timer,$timer_start;
	
	$time_start = microtime(true);
		
	$text = strip_shortcodes($text);
	
	if ($allowed_tags == 'p') $wrap_p = true; else $wrap_p = false;
	
	if ($allowed_tags and !$wrap_p) {
		if (is_array($allowed_tags)) {
			$allowed_tags = '<' . implode('><', $allowed_tags) . '>';
		} else {
			if (mb_strpos($allowed_tags, '<') === false) $allowed_tags = '';
		}
	} elseif ($allowed_tags === false or $wrap_p) {
		if ($wrap_p) $allowed_tags = '<p>'; else $allowed_tags = '';
		$allowed_tags = $allowed_tags . '<a><i><b><s><del><strong>';
	} elseif ($allowed_tags === '') {
		$allowed_tags = '';
	}

	$text = strip_tags($text, $allowed_tags);
	
	if ($find and mb_strlen($text) > $len) {
		$pos = mb_strpos($text, $find, $len);
		if ($pos < $len or $pos > ($len + 20)) $pos = $len;
		$text = mb_substr($text, 0, $pos) . $dots;
	} else {
		$pos = $len;
		$text = mb_substr($text, 0, $pos);
	}
	
	if ($allowed_tags !== '') $text = force_balance_tags($text);
	
	return $text;
		
}


function tw_text($item = false, $len = 250, $allowed_tags = false, $find = ' ', $force_cut = true) {
	
	if (isset($item->post_content)) {
		$text = $item->post_content;
		$excerpt = $item->post_excerpt;
	} else {
		$text = get_the_content();
		$excerpt = get_the_excerpt();
	}
	
	if ($excerpt and mb_strlen($excerpt) > 0) {
		
		if ($force_cut) {
			echo tw_strip($excerpt, $len, $allowed_tags, $find);
		} else {
			echo $excerpt;
		}
		
	} elseif (mb_strpos($text, '<!--more') !== false) {
		
		$pos = mb_strpos($text, '<!--more');
		
		if ($force_cut) {
			echo tw_strip(mb_substr($text, 0, $pos), $len, $allowed_tags, $find);
		} else {
			echo tw_strip(mb_substr($text, 0, $pos), $pos, $allowed_tags, $find);
		}
		
	} else {
		
		echo tw_strip($text, $len, $allowed_tags, $find);
	
	}

}


function tw_image($text) {
	
	preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $text, $matches);
		
	if (isset($matches[1][0]) and $matches[1][0]) {
		return $matches[1][0];
	} else {
		return false;
	}
		
}


function tw_thumb($item, $size = false, $before = '', $after = '', $atts = array(), $thumb_only = false) {
	
	global $_wp_additional_image_sizes;
	
	$width = 0;
	$height = 0;
	$result = '';
	$src = '';
	
	if (!isset($atts['link'])) {
	
		$link = '';

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
	
	if (has_post_thumbnail($item->ID)) {
		
		if (!$src and $link and $thumb = wp_get_attachment_image_src(get_post_thumbnail_id($item->ID), $link)) {
			$src = $thumb[0];
		}
		
		if (!isset($_wp_additional_image_sizes[$size]) and !in_array($size, array('thumbnail', 'medium', 'large', 'full'))) {
			$size = 'thumbnail';
		}
		
		$result = get_the_post_thumbnail($item->ID, $size, $atts);
		
	} elseif (!$thumb_only and $image = tw_image($item->post_content)) {
		
		if (!$src) $src = $image;
		
		if (is_string($size) and $size != 'full') {
			
			if (is_string($size) and isset($_wp_additional_image_sizes[$size])) {
				$width = $_wp_additional_image_sizes[$size]['width'];
				$height = $_wp_additional_image_sizes[$size]['height'];
			} elseif (is_array($size) and $size) {
				if (isset($size[0])) $width = $size[0];
				if (isset($size[1])) $height = $size[1];
			} elseif ($size != 'full') {
				$width = $_wp_additional_image_sizes['thumbnail']['width'];
				$height = $_wp_additional_image_sizes['thumbnail']['height'];
			}
			
			$image = get_template_directory_uri() . '/library/timthumb.php?src=' . $image;
			
			if ($width and is_numeric($width)) {
				$image .= '&w=' . $width;
			}
			
			if ($height and is_numeric($height)) {
				$image .= '&h=' . $height;
			}
			
		}
		
		$result = '<img src="' . $image . '" alt="' . $item->post_title . '"' . ((isset($atts['class'])) ? ' class="' . $atts['class'] . '"' : '') . ' />';
		
	}

	if ($src) {
		
		$before = $before . '<a href="' . $src . '"' . $class . '>';
		
		$after = '</a>' . $after;
		
	}
	
	return $before . $result . $after;
	
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
		echo "По данному адресу записи не обнаружены";
	} elseif (is_search()) {
		echo "По запросу <i>" . get_search_query() . "</i> ничего не найдено";
	}
	
}


function tw_get_views($post_id) {

	$count_key = 'post_views_count';

	$count = get_post_meta($post_id, $count_key, true);

	if ($count == '') {
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '0');
		return "0";
	}

	return $count;

}


function tw_set_views($post_id) {
	
	$count_key = 'post_views_count';
	
	$count = get_post_meta($post_id, $count_key, true);
	
	if ($count=='') {
		$count = 0;
		delete_post_meta($post_id, $count_key);
		add_post_meta($post_id, $count_key, '0');
	} else {
		$count++;
		update_post_meta($post_id, $count_key, $count);
	}

}


function tw_get_rating($post_id) {
	
	$rating_value = get_post_meta($post_id, "rating_value", true);
	$rating_votes = get_post_meta($post_id, "rating_votes", true);
	
	if ($rating_votes == 0) $rating_votes = 1;

	return array(
		'rating' => round($rating_value/$rating_votes),
		'votes' => intval($rating_votes)
	);

}

?>
