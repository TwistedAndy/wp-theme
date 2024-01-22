<?php
/**
 * Breadcrumbs Module
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
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
function tw_breadcrumbs($separator = '', $before = '<nav class="breadcrumbs_box"><div class="fixed">', $after = '</div></nav>') {

	$result = '';

	if (!is_home() and !is_front_page()) {

		$items = tw_breadcrumbs_list();

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

		$current_page = '<span class="last">' . tw_content_heading() . '</span>';

		if ($result) {
			$current_page = $separator . $current_page;
		}

		$result = $before . implode($separator, $result) . $current_page . $after;

	}

	return $result;

}


/**
 * Get the breadcrumb list for the current page
 *
 * @return array
 */
function tw_breadcrumbs_list() {

	$breadcrumbs = [];

	$breadcrumbs[] = [
		'link' => get_site_url(),
		'class' => 'home',
		'title' => __('Home', 'twee')
	];

	$object = get_queried_object();

	if ($object instanceof WP_Post) {

		$post_type = get_post_type_object($object->post_type);

		if (is_singular('product')) {

			$shop_page = get_option('woocommerce_shop_page_id');
			$front_page = get_option('page_on_front');

			if ($shop_page > 0 and $shop_page !== $front_page) {
				$breadcrumbs[] = [
					'link' => get_permalink($shop_page),
					'title' => get_the_title($shop_page)
				];
			}

			$taxonomy = 'product_cat';

		} else {

			if (!empty($post_type->has_archive)) {
				$breadcrumbs[] = [
					'link' => get_post_type_archive_link($post_type->name),
					'title' => $post_type->label
				];
			}

			$taxonomies = get_object_taxonomies($object);

			if (in_array('category', $taxonomies)) {
				$taxonomy = 'category';
			} else {
				$taxonomy = array_shift($taxonomies);
			}

		}

		if ($taxonomy) {

			$current_term = false;

			$terms = get_the_terms($object->ID, $taxonomy);

			if (is_array($terms) and !empty($terms)) {

				foreach ($terms as $term) {

					$current_term = $term;

					if (!empty($term->parent)) {
						break;
					}

				}

			}

			if ($current_term instanceof WP_Term) {

				$terms = get_ancestors($current_term->term_id, $taxonomy);

				if (is_array($terms) and !empty($terms)) {

					$terms = array_reverse($terms);

					foreach ($terms as $term) {
						$term = get_term($term, $taxonomy);
						$breadcrumbs[] = [
							'link' => get_term_link($term->term_id, $taxonomy),
							'title' => $term->name
						];
					}

				}

				$breadcrumbs[] = [
					'link' => get_term_link($current_term->term_id, $taxonomy),
					'title' => $current_term->name
				];

			}

		} else {

			$ancestors = get_post_ancestors($object);

			if (is_array($ancestors) and !empty($ancestors)) {

				$ancestors = array_reverse($ancestors);

				foreach ($ancestors as $ancestor) {
					$breadcrumbs[] = [
						'link' => get_permalink($ancestor),
						'title' => get_the_title($ancestor)
					];
				}

			}

		}

	} elseif ($object instanceof WP_Term) {

		$term_id = $object->term_id;
		$taxonomy = $object->taxonomy;

		if (in_array($taxonomy, ['product_cat', 'product_tag']) or strpos($taxonomy, 'pa_') === 0) {

			$shop_page = get_option('woocommerce_shop_page_id');
			$front_page = get_option('page_on_front');

			if ($shop_page > 0 and $shop_page !== $front_page) {
				$breadcrumbs[] = [
					'link' => get_permalink($shop_page),
					'title' => get_the_title($shop_page)
				];
			}

		}

		if ($terms = get_ancestors($term_id, $taxonomy)) {

			$terms = array_reverse($terms);

			foreach ($terms as $term) {

				$term = get_term($term, $taxonomy);

				$breadcrumbs[] = [
					'link' => get_term_link($term->term_id, $taxonomy),
					'title' => $term->name
				];

			}

		}

	}

	$breadcrumbs = apply_filters('twee_breadcrumbs', $breadcrumbs);

	return $breadcrumbs;

}


/**
 * Inject the JSON-LD microdata
 */
add_action('wp_head', 'tw_breadcrumbs_json');

function tw_breadcrumbs_json() {

	$breadcrumbs = tw_breadcrumbs_list();

	if (is_array($breadcrumbs) and count($breadcrumbs) > 1) {

		$schema = [
			'@context' => 'http://schema.org',
			'@type' => 'BreadcrumbList',
			'itemListElement' => []
		];

		foreach ($breadcrumbs as $key => $breadcrumb) {

			$schema['itemListElement'][] = [
				'@type' => 'ListItem',
				'position' => ($key + 1),
				'item' => [
					'@id' => $breadcrumb['link'],
					'name' => $breadcrumb['title']
				]
			];
		}

		echo "<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n</script>\n";

	}

}