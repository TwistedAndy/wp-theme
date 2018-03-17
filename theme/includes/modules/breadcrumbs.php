<?php
/**
 * Breadcrumbs library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Build the breadcrumbs HTML code for the current page
 *
 * @param string $separator Code between the breadcrumbs
 * @param string $before    Code before the breadcrumbs
 * @param string $after     Code after the breadcrumbs
 *
 * @return string
 */

function tw_breadcrumbs($separator = '', $before = '<div class="breadcrumbs">', $after = '</div>') {

	$result = '';

	if (!is_home() and !is_front_page()) {

		$breadcrumbs = tw_get_breadcrumb_list();
		$count = count($breadcrumbs);
		$current_page = '';

		if (tw_get_setting('modules', 'breadcrumbs', 'include_current')) {
			$current_page = '<span class="last">' . tw_wp_title() . '</span>';
		} elseif ($count > 1) {
			$breadcrumbs[$count - 1]['class'] = 'last';
		} else {
			return $result;
		}

		$microdata = tw_get_setting('modules', 'breadcrumbs', 'microdata');

		if ($microdata == 'inline') {
			$before = substr_replace($before, ' xmlns:v="http://rdf.data-vocabulary.org/#">', strrpos($before, '>'), 1);
			$microdata = true;
		} else {
			$microdata = false;
		}

		$result = $before . tw_build_breadcrumb($breadcrumbs, $separator, $microdata, true) . $current_page . $after;

	}

	return $result;

}


/**
 * Build a single breadcrumb
 *
 * @param array  $breadcrumbs Array with the breadcrumbs
 * @param string $separator   Code between the breadcrumbs
 * @param bool   $microdata   Include the microdata for the search engines
 * @param bool   $first
 *
 * @return string
 */

function tw_build_breadcrumb($breadcrumbs, $separator, $microdata = false, $first = false) {

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

			$result .= tw_build_breadcrumb($breadcrumbs, $separator, $microdata);

		}

	}

	return $result;

}


/**
 * Get the breadcrumb list for the current page
 *
 * @return array
 */

function tw_get_breadcrumb_list() {

	$breadcrumbs = tw_get_setting('cache', 'breadcrumbs');

	if (!$breadcrumbs) {

		$breadcrumbs = array();

		$breadcrumbs[] = array(
			'link' => get_site_url(),
			'class' => 'home',
			'title' => __('Home', 'wp-theme')
		);

		if (is_singular()) {

			if (tw_get_setting('modules', 'breadcrumbs', 'include_archive')) {

				$post_type = get_post_type_object(get_post_type());

				if (!empty($post_type->has_archive)) {
					$breadcrumbs[] = array(
						'link' => get_post_type_archive_link($post_type->name),
						'title' => $post_type->label
					);
				}

			}

			$current_term = false;

			$taxonomy = tw_current_taxonomy();

			if ($taxonomy) {

				$terms = get_the_terms(get_the_ID(), $taxonomy);

				if (is_array($terms) and !empty($terms)) {

					foreach ($terms as $term) {

						$current_term = $term;

						if (!empty($term->parent)) {
							break;
						}

					}

				}

			}

			if ($current_term and !empty($current_term->term_id)) {

				$terms = get_ancestors($current_term->term_id, $taxonomy);

				if (is_array($terms) and !empty($terms)) {

					$terms = array_reverse($terms);

					foreach ($terms as $term) {
						$term = get_term($term, $taxonomy);
						$breadcrumbs[] = array(
							'link' => get_term_link($term->term_id, $taxonomy),
							'title' => $term->name
						);
					}

				}

				$breadcrumbs[] = array(
					'link' => get_term_link($current_term->term_id, $taxonomy),
					'title' => $current_term->name
				);

			} else {

				$ancestors = get_post_ancestors(get_post());

				if (is_array($ancestors) and !empty($ancestors)) {

					$ancestors = array_reverse($ancestors);

					foreach ($ancestors as $ancestor) {
						$breadcrumbs[] = array(
							'link' => get_permalink($ancestor),
							'title' => get_the_title($ancestor)
						);
					}

				}

			}

		} elseif (is_category() or is_tax()) {

			if (is_tax() and $term_object = get_queried_object()) {
				$term_id = $term_object->term_id;
				$taxonomy = get_query_var('taxonomy');
			} else {
				$term_id = get_query_var('cat');
				$taxonomy = 'category';
			}

			if ($term_id and $terms = get_ancestors($term_id, $taxonomy)) {

				$terms = array_reverse($terms);

				foreach ($terms as $term) {

					$term = get_term($term, $taxonomy);

					$breadcrumbs[] = array(
						'link' => get_term_link($term->term_id, $taxonomy),
						'title' => $term->name
					);

				}

			}

		}

		$breadcrumbs = apply_filters('tw_breadcrumbs', $breadcrumbs);

		tw_set_setting('cache', 'breadcrumbs', $breadcrumbs);

	}

	return $breadcrumbs;

}


/**
 * Include the JSON-LD Breadcrumbs microdata to the page
 */

if (tw_get_setting('modules', 'breadcrumbs', 'microdata') == 'json') {

	add_action('wp_head', 'tw_breadcrumbs_json');

	function tw_breadcrumbs_json() {

		$breadcrumbs = tw_get_breadcrumb_list();

		if (is_array($breadcrumbs) and count($breadcrumbs) > 1) {

			$schema = array(
				'@context' => 'http://schema.org',
				'@type' => 'BreadcrumbList',
				'itemListElement' => array()
			);

			$i = 1;

			foreach ($breadcrumbs as $breadcrumb) {

				$schema['itemListElement'][] = array(
					'@type' => 'ListItem',
					'position' => $i,
					'item' => array(
						'@id' => $breadcrumb['link'],
						'name' => $breadcrumb['title']
					)
				);

				$i++;

			}

			echo "<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n</script>\n";

		}

	}

}