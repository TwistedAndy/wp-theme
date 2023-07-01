<?php
/**
 * Load more posts
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

add_action('wp_ajax_nopriv_loader', 'tw_loader_handle');
add_action('wp_ajax_loader', 'tw_loader_handle');

function tw_loader_handle() {

	$result = [
		'result' => '',
		'more' => false
	];

	if (empty($_REQUEST['number']) or empty($_POST['noncer']) or !wp_verify_nonce($_POST['noncer'], 'ajax-nonce')) {
		wp_send_json($result);
		return;
	}

	$fields = ['offset', 'number', 'author'];

	$params = [];

	foreach ($fields as $field) {
		if (isset($_REQUEST[$field])) {
			$params[$field] = intval($_REQUEST[$field]);
		} else {
			$params[$field] = 0;
		}
	}

	if (!empty($_REQUEST['template'])) {
		$template = esc_attr($_REQUEST['template']);
	} else {
		$template = 'post';
	}

	$args = [
		'post_status' => 'publish',
		'offset' => $params['offset'],
		'posts_per_page' => $params['number']
	];

	if (!empty($_REQUEST['search'])) {
		$args['s'] = esc_attr($_REQUEST['search']);
	}

	if (!empty($_REQUEST['type'])) {

		$types = array_values(get_post_types(['publicly_queryable' => true]));

		if (!is_array($_REQUEST['type'])) {
			$_REQUEST['type'] = [$_REQUEST['type']];
		}

		$args['post_type'] = array_unique(array_intersect($types, $_REQUEST['type']));

	}

	$tax_query = [];

	if (!empty($_REQUEST['query_tax']) and is_array($_REQUEST['query_tax'])) {

		foreach ($_REQUEST['query_tax'] as $key => $value) {

			if ($key === 'relation' and in_array($value, ['AND', 'OR'])) {
				$tax_query['relation'] = $value;
			}

			if (is_numeric($key) and is_array($value) and !empty($value['taxonomy']) and !empty($value['terms'])) {
				$tax_query[] = $value;
			}

		}

	}

	if (!empty($_REQUEST['terms']) and is_array($_REQUEST['terms'])) {

		$terms = [];

		foreach ($_REQUEST['terms'] as $term_id) {

			$term = get_term(intval($term_id));

			if ($term instanceof WP_Term) {
				$terms[$term->taxonomy][] = $term->term_id;
			}

		}

		/**
		 * Terms specified in the terms field have a higher priority than tax query
		 */
		if ($terms) {

			foreach ($terms as $taxonomy => $term_ids) {

				if ($tax_query) {
					foreach ($tax_query as $index => $data) {
						if (!empty($data['taxonomy']) and $data['taxonomy'] == $taxonomy) {
							unset($tax_query[$index]);
						}
					}
				}

				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $term_ids,
					'operator' => 'IN'
				];

			}

		}

	}

	if ($tax_query) {

		if (empty($tax_query['relation'])) {
			$tax_query['relation'] = 'AND';
		}

		$args['tax_query'] = $tax_query;

	}

	$meta_query = [];

	if (!empty($_REQUEST['query_meta']) and is_array($_REQUEST['query_meta'])) {
		$meta_query = $_REQUEST['query_meta'];
	}

	if (!empty($_REQUEST['query_order'])) {

		$order_parts = [];

		if (is_array($_REQUEST['query_order'])) {

			$order = [];

			foreach ($_REQUEST['query_order'] as $key => $value) {
				$key = trim(esc_attr($key));
				$value = trim(esc_attr($value));
				$order[$key] = $value;
				$order_parts[] = $key;
			}

		} else {

			$order = esc_sql($_REQUEST['query_order']);
			$order_parts = explode(' ', $order);
			$order_parts = array_map('trim', $order_parts);

		}

		$args['orderby'] = $order;

		if (!empty($_REQUEST['query_direction']) and in_array($_REQUEST['query_direction'], ['ASC', 'DESC'])) {
			$args['order'] = $_REQUEST['query_direction'];
		}

		if (in_array('title', $order_parts) and empty($args['order'])) {

			$args['order'] = 'ASC';

		} elseif (in_array('best', $order_parts) and empty($meta_query['sales'])) {

			$meta_query['sales'] = [
				'key' => 'total_sales',
				'compare' => 'EXISTS',
				'type' => 'NUMERIC'
			];

		} elseif (in_array('shuffle', $order_parts) and empty($meta_query['shuffle'])) {

			$meta_query['shuffle'] = [
				'key' => '_shuffle_order',
				'compare' => 'EXISTS',
				'type' => 'NUMERIC'
			];

		}

	}

	if ($meta_query) {
		$args['meta_query'] = $meta_query;
	}

	foreach (['post__in', 'post__not_in'] as $param) {

		if (!empty($_REQUEST[$param]) and is_array($_REQUEST[$param])) {

			foreach ($_REQUEST[$param] as $key => $value) {
				if (is_numeric($value)) {
					$_REQUEST[$param][$key] = intval($value);
				} else {
					unset($_REQUEST[$param][$key]);
				}
			}

			if (!empty($_REQUEST[$param])) {
				$args[$param] = $_REQUEST[$param];
			}

		}

	}

	if (!empty($_REQUEST['author'])) {
		$args['author'] = $params['author'];
	}

	$query = new WP_Query($args);

	if ($query->have_posts()) {

		if (($params['number'] + $params['offset']) < $query->found_posts) {
			$result['more'] = true;
		}

		while ($query->have_posts()) {
			$query->the_post();
			$result['result'] .= tw_template_part($template, $query->post);
		}

	} else {

		$result['result'] = '<div class="message">Nothing had been found</div>';

	}

	wp_send_json($result);

}


/**
 * Show the load more button
 *
 * @param string         $wrapper
 * @param string         $template
 * @param WP_Query|false $query
 * @param int            $number
 * @param bool           $is_hidden
 *
 * @return void
 */
function tw_loader_button($wrapper, $template = 'post', $query = false, $number = false, $is_hidden = false) {

	global $wp_query;

	if (empty($query) or !($query instanceof WP_Query)) {
		$query = $wp_query;
	}

	$posts_per_page = intval($query->query_vars['posts_per_page']);

	$paged = intval($query->query_vars['paged']);

	if ($paged < 1) {
		$paged = 1;
	}

	if (empty($number)) {
		$number = $posts_per_page;
	}

	if ($posts_per_page > 0) {
		$offset = $paged * $posts_per_page;
	} else {
		$offset = 0;
	}

	$hidden = true;

	if ($offset < $query->found_posts and $posts_per_page > 0) {
		$hidden = false;
	}

	$type = $query->query_vars['post_type'];
	$object = $query->get_queried_object();
	$tax_query = $query->get('tax_query');

	if (!is_array($tax_query) or empty($tax_query)) {
		$tax_query = [];
	}

	if ($object instanceof WP_Term) {

		if (empty($type) and $taxonomy = get_taxonomy($object->taxonomy)) {
			$type = $taxonomy->object_type;
		}

		$add_query = true;

		foreach ($tax_query as $tax) {
			if (is_array($tax) and !empty($tax['taxonomy']) and $tax['taxonomy'] == $object->taxonomy) {
				$add_query = false;
				break;
			}
		}

		if ($add_query) {
			$tax_query['relation'] = 'AND';
			$tax_query[] = [
				'taxonomy' => $object->taxonomy,
				'terms' => [$object->term_id],
				'operator' => 'IN'
			];
		}

	}

	if ($query->is_search()) {
		$search = get_search_query();
	} else {
		$search = '';
	}

	$author = $query->get('author');

	$post_in = $query->get('post__in');

	if (!is_array($post_in)) {
		$post_in = [];
	}

	$post_not = $query->get('post__not_in');

	if (!is_array($post_not)) {
		$post_not = [];
	}

	$args = [
		'number' => $number,
		'offset' => $offset,
		'type' => $type,
		'terms' => [],
		'search' => $search,
		'wrapper' => $wrapper,
		'template' => $template,
		'author' => $author,
		'post__in' => $post_in,
		'post__not_in' => $post_not,
		'query_tax' => $tax_query,
		'query_meta' => $query->get('meta_query'),
		'query_order' => $query->get('orderby'),
		'query_direction' => $query->get('order')
	];

	?>

	<div class="buttons<?php echo $is_hidden ? ' hidden' : ''; ?>">
		<div class="button outline<?php echo($hidden ? ' is_hidden' : ''); ?>" data-loader="<?php echo htmlspecialchars(json_encode($args), ENT_QUOTES, 'UTF-8'); ?>"><?php _e('Show More', 'twee'); ?></div>
	</div>

	<?php

}