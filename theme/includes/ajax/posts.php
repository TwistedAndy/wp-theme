<?php
/**
 * Load more posts using AJAX
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

/*
add_action('wp_ajax_nopriv_load_posts', 'tw_ajax_load_posts');
add_action('wp_ajax_load_posts', 'tw_ajax_load_posts');
*/

function tw_ajax_load_posts() {

	$result = array(
		'result' => '',
		'more' => false
	);

	if (isset($_POST['noncer']) and wp_verify_nonce($_POST['noncer'], 'ajax-nonce')) {

		$fields = array('offset', 'number', 'author');

		$params = array();

		foreach ($fields as $field) {
			if (isset($_REQUEST[$field])) {
				$params[$field] = intval($_REQUEST[$field]);
			} else {
				$params[$field] = 0;
			}
		}

		$template = 'post';

		if (!empty($_REQUEST['template']) and in_array($_REQUEST['template'], array('post', 'offer', 'coupon'))) {
			$template = esc_attr($_REQUEST['template']);
		}

		if ($params['number'] > 0) {

			$args = array(
				'post_status' => 'publish',
				'offset' => $params['offset'],
				'posts_per_page' => $params['number']
			);

			if (!empty($_REQUEST['search'])) {
				$args['s'] = esc_attr($_REQUEST['search']);
			}

			if (!empty($_REQUEST['type'])) {

				$types = get_post_types(array('publicly_queryable' => true));

				if (in_array($_REQUEST['type'], $types)) {
					$args['post_type'] = $_REQUEST['type'];
				}

			}

			if (!empty($_REQUEST['terms']) and is_array($_REQUEST['terms'])) {

				$tax_query = array();

				$terms = array();

				foreach ($_REQUEST['terms'] as $term_id) {

					$term_id = intval($term_id);

					if ($term_id > 0) {

						$term = get_term($term_id);

						if ($term instanceof WP_Term) {

							$terms[$term->taxonomy][] = $term->term_id;

						}

					}

				}

				foreach ($terms as $taxonomy => $ids) {

					$tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field' => 'term_id',
						'terms' => $ids,
						'operator' => 'IN'
					);

				}

				if ($tax_query) {

					$args['tax_query'] = array_merge(array('relation' => 'AND'), $tax_query);

				}

			}

			if (!empty($_REQUEST['author'])) {
				$args['author'] = $params['author'];
			}

			if (!empty($_REQUEST['query_meta']) and is_array($_REQUEST['query_meta'])) {
				$args['meta_query'] = $_REQUEST['query_meta'];
			}

			if (!empty($_REQUEST['query_order']) and is_array($_REQUEST['query_order'])) {

				foreach ($_REQUEST['query_order'] as $key => $value) {
					$key = trim(esc_attr($key));
					$value = trim(esc_attr($value));
					$args['orderby'][$key] = $value;
				}

			}

			if (!empty($_REQUEST['query_direction']) and in_array($_REQUEST['query_direction'], array('ASC', 'DESC'))) {
				$args['order'] = $_REQUEST['query_direction'];
			}

			if (!empty($_REQUEST['order']) and in_array($_REQUEST['order'], array('date', 'views', 'title', 'comment_count', 'author'))) {

				$order = esc_attr($_REQUEST['order']);

				$args['orderby'] = $order;

				$args['order'] = 'DESC';

				if ($order == 'title') {

					$args['order'] = 'ASC';

				} elseif ($order == 'views') {

					$args['meta_query']['views'] = array(
						'key' => 'views',
						'compare' => 'EXISTS',
					);

				}

				if (is_array($args['orderby'])) {

					$args['orderby'][$order] = $args['order'];

				} else {

					$args['orderby'] = array(
						$order => $args['order']
					);

				}

			}

			$query = new WP_Query($args);

			if ($query->have_posts()) {

				if (($params['number'] + $params['offset']) < $query->found_posts) {
					$result['more'] = true;
				}

				ob_start();

				while ($query->have_posts()) {

					$query->the_post();

					tw_template_part($template, $query->post);

				}

				$result['result'] = ob_get_contents();

			} else {

				$result['result'] = '<div class="message">Nothing had been found</div>';

			}

			ob_end_clean();

		}

	}

	wp_send_json($result);

}


function tw_ajax_load_button($wrapper, $template = 'post', $query = false, $number = false) {

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

	$terms = array();
	$search = '';
	$type = get_post_type($query->post);
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

	$args = array(
		'number' => $number,
		'offset' => $offset,
		'type' => $type,
		'terms' => $terms,
		'search' => $search,
		'wrapper' => $wrapper,
		'template' => $template,
		'author' => $author,
		'query_meta' => $query->get('meta_query'),
		'query_order' => $query->get('orderby'),
		'query_direction' => $query->get('order')
	);

	?>

	<div class="buttons">
		<div class="button outlined<?php echo ($hidden ? ' hidden' : ''); ?>" data-loader="<?php echo htmlspecialchars(json_encode($args), ENT_QUOTES, 'UTF-8'); ?>">Show More</div>
	</div>

	<?php

}


/*

jQuery(function($){

	$('.button[data-loader]').each(function() {

		var button = $(this), data = button.data('loader');

		var offset = data.offset;

		var section = button.parents(data.wrapper);

		var wrapper = section.find('.items');

		wrapper.on('reset', function() {

			wrapper.css('height', wrapper.outerHeight());

			wrapper.children().remove();

			offset = 0;

			data.offset = 0;

			button.trigger('click');

		});

		button.click(function() {

			data = button.data('loader');

			data.action = 'load_posts';

			data.noncer = template.nonce;

			$.ajax({
				url: template.ajaxurl,
				type: 'post',
				dataType: 'json',
				data: data,
				success: function(response) {

					if (response['result']) {

						var posts = $(response['result']);

						wrapper.append(posts);

						wrapper.css('height', 'auto');

						offset = offset + data.number;

						data.offset = offset;

						if (response['more']) {
							button.removeClass('hidden');
						} else {
							button.addClass('hidden');
						}

						section.trigger('init');

					} else {

						button.addClass('hidden');

					}

				}

			});

		});

	});

});
*/