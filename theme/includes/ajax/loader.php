<?php
/**
 * Load more posts using AJAX
 *
 * @author  Andrii Toniievych <andy@absoluteweb.com>
 * @package Twee
 * @version 3.0
 */

/*
add_action('wp_ajax_nopriv_loader', 'tw_ajax_loader');
add_action('wp_ajax_loader', 'tw_ajax_loader');
*/

function tw_ajax_loader() {

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

	$template = 'post';

	if (!empty($_REQUEST['template'])) {
		$template = esc_attr($_REQUEST['template']);
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

			if (is_numeric($key) and is_array($value) and !empty($value['terms'])) {
				$tax_query[] = $value;
			}

		}

	}

	if (!empty($_REQUEST['terms']) and is_array($_REQUEST['terms'])) {

		$terms = [];

		foreach ($_REQUEST['terms'] as $term_id) {

			$term = get_term($term_id);

			if ($term instanceof WP_Term) {
				$terms[$term->taxonomy][] = $term->term_id;
			}

		}

		if ($terms) {

			foreach ($terms as $taxonomy => $ids) {

				/**
				 * Remove existing queries if the terms field specified
				 */
				if ($tax_query) {
					foreach ($tax_query as $index => $data) {
						if (!empty($data['taxonomy']) and $data['taxonomy'] == $taxonomy and array_intersect($data['terms'], $ids)) {
							unset($tax_query[$index]);
						}
					}
				}

				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $ids,
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

	if (!empty($_REQUEST['query_meta']) and is_array($_REQUEST['query_meta'])) {
		$args['meta_query'] = $_REQUEST['query_meta'];
	}

	if (!empty($_REQUEST['query_order'])) {

		if (is_array($_REQUEST['query_order'])) {

			foreach ($_REQUEST['query_order'] as $key => $value) {
				$key = trim(esc_attr($key));
				$value = trim(esc_attr($value));
				$args['orderby'][$key] = $value;
			}

		} else {

			$args['orderby'] = trim(esc_sql($_REQUEST['query_order']));

		}

	}

	if (!empty($_REQUEST['query_direction']) and in_array($_REQUEST['query_direction'], ['ASC', 'DESC'])) {
		$args['order'] = $_REQUEST['query_direction'];
	}

	if (!empty($_REQUEST['order']) and in_array($_REQUEST['order'], ['date', 'best', 'shuffle', 'random', 'title', 'comment_count', 'author'])) {

		$order = esc_attr($_REQUEST['order']);

		$args['orderby'] = $order;

		$args['order'] = 'DESC';

		if ($order == 'title') {

			$args['order'] = 'ASC';

		} elseif ($order == 'best') {

			$args['meta_query']['sales'] = [
				'key' => 'total_sales',
				'compare' => 'EXISTS',
				'type' => 'NUMERIC'
			];

		} elseif ($order == 'shuffle') {

			$args['meta_query']['shuffle'] = [
				'key' => '_shuffle_order',
				'compare' => 'EXISTS',
				'type' => 'NUMERIC'
			];

		} elseif ($order == 'random') {

			$args['meta_query']['random'] = [
				'key' => '_random_order',
				'compare' => 'EXISTS',
				'type' => 'NUMERIC'
			];

		}

		if (is_array($args['orderby'])) {

			$args['orderby'][$order] = $args['order'];

		} else {

			$args['orderby'] = $order;

		}

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


function tw_loader_button($wrapper, $template = 'post', $query = false, $number = false, $is_hidden = false) {

	global $wp_query;

	if (empty($query) or !($query instanceof WP_Query)) {
		$query = $wp_query;
	}

	$posts_per_page = intval($query->query_vars['posts_per_page']);

	$paged = intval($query->query_vars['paged']);

	if ($paged == 0) {
		$paged = 1;
	}

	if ($number == false) {
		$number = $posts_per_page;
	}

	$offset = $paged * $posts_per_page;

	$hidden = true;

	if ($offset < $query->found_posts) {
		$hidden = false;
	}

	$terms = [];
	$search = '';
	$type = $query->query_vars['post_type'];
	$object = $query->get_queried_object();

	if ($object instanceof WP_Term) {

		$terms[] = $object->term_id;

		$tax_query = $query->get('tax_query');

		if ($tax_query) {

			foreach ($tax_query as $tax) {

				if (is_array($tax) and is_array($tax['terms']) and (empty($tax['operator']) or $tax['operator'] == 'IN')) {

					$terms = array_merge($terms, $tax['terms']);

				}

			}

		}

		$terms = array_values(array_unique($terms));

	}

	if ($query->is_search()) {
		$search = get_search_query();
	}

	if ($query->is_author()) {
		$author = get_queried_object_id();
	} else {
		$author = 0;
	}

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
		'terms' => $terms,
		'search' => $search,
		'wrapper' => $wrapper,
		'template' => $template,
		'author' => $author,
		'post__in' => $post_in,
		'post__not_in' => $post_not,
		'query_tax' => $query->get('tax_query'),
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