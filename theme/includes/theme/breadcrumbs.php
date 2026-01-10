<?php
/**
 * Breadcrumbs Module
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */

/**
 * Build the breadcrumbs HTML code for the current page
 *
 * @param string        $separator Code between the breadcrumbs
 * @param string        $before    Code before the breadcrumbs
 * @param string        $after     Code after the breadcrumbs
 * @param WP_Query|null $query
 * @param bool          $current
 *
 * @return string
 */
function tw_breadcrumbs(string $separator = '', string $before = '<nav class="breadcrumbs_box"><div class="fixed">', string $after = '</div></nav>', $query = null, bool $current = true): string
{

	$result = '';

	if (empty($query)) {
		global $wp_query;
		$query = $wp_query;
	}

	if (!($query instanceof WP_Query)) {
		return $result;
	}

	$items = tw_breadcrumbs_list($query);

	if (empty($items)) {
		return $result;
	}

	$result = [];

	foreach ($items as $item) {

		if (empty($item['title'])) {
			continue;
		}

		if (empty($item['class'])) {
			$class = '';
		} else {
			$class = ' class="' . $item['class'] . '"';
		}

		if (!empty($item['link'])) {
			$result[] = '<a href="' . $item['link'] . '"' . $class . '>' . $item['title'] . '</a>';
		}

	}

	if (empty($result)) {
		return '';
	}

	if ($current) {
		return $before . implode($separator, $result) . $separator . '<span class="last">' . tw_content_heading($query) . '</span>' . $after;
	}

	return $before . implode($separator, $result) . $after;
}


/**
 * Get the breadcrumb list for the WP_Query object
 *
 * @param WP_Query|null $query
 *
 * @return array
 */
function tw_breadcrumbs_list($query = null): array
{
	$breadcrumbs = [];

	if (empty($query)) {
		global $wp_query;
		$query = $wp_query;
	}

	if (!($query instanceof WP_Query)) {
		return $breadcrumbs;
	}

	if ($query->is_front_page()) {
		return $breadcrumbs;
	}

	$breadcrumbs['home'] = [
		'link'  => get_site_url(),
		'class' => 'home',
		'title' => __('Home', 'twee')
	];

	$object = $query->get_queried_object();

	$post_id = 0;
	$post_type = '';
	$parent_terms = [];
	$taxonomy = '';

	if ($object instanceof WP_Post) {

		$post_id = $object->ID;
		$post_type = $object->post_type;

		if ($post_type == 'post') {

			$taxonomy = 'category';

		} elseif ($post_type == 'product') {

			$taxonomy = 'product_cat';

		} else {

			$taxonomies = get_object_taxonomies($post_type, 'objects');

			if ($taxonomies) {

				$taxonomy = array_key_first($taxonomies);

				foreach ($taxonomies as $key => $taxonomy_object) {
					if ($taxonomy_object->hierarchical) {
						$taxonomy = $key;
						break;
					}
				}

			}

		}

		if ($taxonomy) {

			if (defined('RANK_MATH_VERSION')) {
				$current_term = tw_meta_get('post', $post_id, 'rank_math_primary_' . $taxonomy);
			} elseif (defined('WPSEO_VERSION')) {
				$current_term = tw_meta_get('post', $post_id, '_yoast_wpseo_primary_' . $taxonomy);
			} else {
				$current_term = 0;
			}

			$map = tw_post_terms($taxonomy);

			if (!empty($map[$post_id]) and is_array($map[$post_id])) {

				$terms = $map[$post_id];

				if ($current_term > 0 and !in_array($current_term, $terms)) {
					$current_term = 0;
				}

				if (empty($current_term)) {

					$count = 0;

					foreach ($terms as $term_id) {
						$thread = tw_term_ancestors($term_id, $taxonomy);
						if (count($thread) >= $count) {
							$current_term = (int) $term_id;
							$parent_terms = $thread;
							$count = count($thread);
						}
					}

					if ($current_term > 0) {
						if (defined('RANK_MATH_VERSION')) {
							tw_meta_update('post', $post_id, 'rank_math_primary_' . $taxonomy, $current_term);
						} elseif (defined('WPSEO_VERSION')) {
							tw_meta_update('post', $post_id, '_yoast_wpseo_primary_' . $taxonomy, $current_term);
						}
					}

				} else {

					$parent_terms = tw_term_ancestors($current_term, $taxonomy);

				}

			}

			if ($current_term > 0) {
				$parent_terms = array_reverse($parent_terms);
				$parent_terms[] = $current_term;
			}

		}

	} elseif ($object instanceof WP_Term) {

		$taxonomy = $object->taxonomy;

		$taxonomy_object = get_taxonomy($taxonomy);

		if ($taxonomy_object instanceof WP_Taxonomy and is_array($taxonomy_object->object_type)) {
			$post_type = reset($taxonomy_object->object_type);
		}

		if ($taxonomy_object->hierarchical and $ancestors = tw_term_ancestors($object->term_id, $taxonomy)) {
			$parent_terms = array_reverse($ancestors);
		}

	}

	if ($post_type) {

		$link = get_option('options_link_' . $post_type, false);

		if (is_array($link) and !empty($link['url'])) {
			$breadcrumbs['archive'] = [
				'link'  => $link['url'],
				'title' => $link['title'],
				'type'  => $post_type
			];
		}

	}

	if ($parent_terms and $taxonomy) {

		$labels = tw_term_data('term_id', 'name');

		foreach ($parent_terms as $term_id) {

			$link = tw_term_link($term_id, $taxonomy);

			if ($link and !empty($labels[$term_id])) {
				$breadcrumbs['term_' . $term_id] = [
					'link'     => $link,
					'title'    => $labels[$term_id],
					'taxonomy' => $taxonomy,
					'term'     => $term_id
				];
			}

		}

	} elseif ($post_id > 0 and $post_type) {

		$type_object = get_post_type_object($post_type);

		if ($type_object instanceof WP_Post_Type and $type_object->hierarchical) {

			$ancestors = get_post_ancestors($object);

			if (is_array($ancestors) and $ancestors) {

				$ancestors = array_reverse($ancestors);

				foreach ($ancestors as $ancestor) {
					if ($post = get_post($ancestor)) {
						$breadcrumbs['post_' . $ancestor] = [
							'link'  => get_permalink($post),
							'title' => $post->post_title,
							'type'  => $post_type,
							'post'  => $ancestor
						];
					}
				}

			}

		}

	}

	return apply_filters('twee_breadcrumbs', $breadcrumbs, $query);
}


/**
 * Inject the JSON-LD microdata
 */
add_action('wp_head', 'tw_breadcrumbs_json', 20);

function tw_breadcrumbs_json(): void
{
	$breadcrumbs = tw_breadcrumbs_list();

	if (count($breadcrumbs) < 2) {
		return;
	}

	$schema = [
		'@context'        => 'http://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => []
	];

	$position = 1;

	foreach ($breadcrumbs as $breadcrumb) {
		$schema['itemListElement'][] = [
			'@type'    => 'ListItem',
			'position' => $position,
			'item'     => [
				'@id'  => $breadcrumb['link'],
				'name' => $breadcrumb['title']
			]
		];

		$position++;
	}

	echo "<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n</script>\n";
}