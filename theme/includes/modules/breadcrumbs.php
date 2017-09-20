<?php
/**
 * Breadcrumbs library
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Build the breadcrumbs for the current page
 *
 * @param string $before    Code to prepend to the breadcrumbs
 * @param string $after     Code to append to the breadcrumbs
 * @param string $separator Code between the breadcrumbs
 * @param bool $microdata   Include the microdata for the search engines
 *
 * @return string
 */

function tw_breadcrumbs($separator = '', $before = '<div class="breadcrumbs">', $after = '</div>', $microdata = true) {

	$result = '';
	$breadcrumbs = array();

	if (!is_home() and !is_front_page()) {

		$breadcrumbs[] = array(
			'link' => get_site_url(),
			'class' => 'home',
			'title' => __('Home', 'wp-theme')
		);

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
					$breadcrumbs[] = array(
						'link' => get_term_link($category->term_id, $taxonomy),
						'title' => $category->name
					);
				}

				$breadcrumbs[] = array(
					'link' => get_term_link($term->term_id, $taxonomy),
					'title' => $term->name
				);

			} else {

				$term = tw_current_term(true);

				$breadcrumbs[] = array(
					'link' => get_term_link($term->term_id, $taxonomy),
					'title' => $term->name
				);

			}

		} elseif (is_page()) {

			$pages = get_ancestors(get_the_ID(), 'page');

			if ($pages) {

				$pages = array_reverse($pages);

				foreach ($pages as $page) {

					$page = get_post($page);

					$breadcrumbs[] = array(
						'link' => get_page_link($page),
						'title' => $page->post_title
					);

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

					$breadcrumbs[] = array(
						'link' => get_term_link($category->term_id, $taxonomy),
						'title' => $category->name
					);

				}

			}

		}

		if ($microdata) {
			$before = substr_replace($before, ' xmlns:v="http://rdf.data-vocabulary.org/#">', strrpos($before, '>'), 1);
		}

		$result = $before . tw_build_breadcrumb($breadcrumbs, $separator, $microdata, true) . '<span class="last">' . tw_wp_title() . '</span>' . $after;

	}

	return $result;

}


/**
 * Build a single breadcrumb
 *
 * @param array $breadcrumbs Array with breadcrumbs
 * @param string $separator  Code between the breadcrumbs
 * @param bool $microdata    Include the microdata for the search engines
 * @param bool $first
 *
 * @return string
 */

function tw_build_breadcrumb($breadcrumbs, $separator, $microdata = true, $first = false) {

	$result = '';

	if (!empty($breadcrumbs)) {

		$breadcrumb = array_shift($breadcrumbs);

		$class = '';

		if (!empty($breadcrumb['class'])) {
			$class = ' class="' . $breadcrumb['class'] . '"';
		}

		if ($microdata) {

			if ($first) {
				$result = '<span typeof="v:Breadcrumb">';
			} else {
				$result = '<span typeof="v:Breadcrumb" rel="v:child">';
			}

			$result .= '<a href="' . $breadcrumb['link'] . '"' . $class . ' rel="v:url" property="v:title">' . $breadcrumb['title'] . '</a>' . $separator;

			$result .= tw_build_breadcrumb($breadcrumbs, $separator, $microdata);

			$result .= '</span>';

		} else {

			$result = '<a href="' . $breadcrumb['link'] . '"' . $class . '>' . $breadcrumb['title'] . '</a>' . $separator;

			tw_build_breadcrumb($breadcrumbs, $separator, $microdata);

		}

	}

	return $result;

}