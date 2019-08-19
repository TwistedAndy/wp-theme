<?php
/**
 * Load more posts using AJAX
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */


add_action('wp_ajax_nopriv_load_posts', 'tw_ajax_load_posts');
add_action('wp_ajax_load_posts', 'tw_ajax_load_posts');

function tw_ajax_load_posts() {

	if (isset($_POST['noncer']) and wp_verify_nonce($_POST['noncer'], 'ajax-nonce')) {

		$fields = array('object', 'offset', 'number');

		$params = array();

		foreach ($fields as $field) {
			if (isset($_REQUEST[$field])) {
				$params[$field] = intval($_REQUEST[$field]);
			} else {
				$params[$field] = 0;
			}
		}

		$template = 'post';

		if (!empty($_REQUEST['template']) and in_array($_REQUEST['template'], array('post', 'testimonial'))) {
			$template = esc_attr($_REQUEST['template']);
		}

		if ($params['number'] > 0) {

			$args = array(
				'numberposts' => $params['number'],
				'offset' => $params['offset']
			);

			if (!empty($_REQUEST['search'])) {
				$args['s'] = esc_attr($_REQUEST['search']);
			}

			if (isset($_REQUEST['type'])) {

				$types = get_post_types(array('publicly_queryable' => true));

				if (in_array($_REQUEST['type'], $types)) {

					$args['post_type'] = $_REQUEST['type'];

				}

			}

			if ($params['object'] > 0) {

				$term = get_term($params['object']);

				if ($term instanceof WP_Term) {

					$args['tax_query'] = array(
						array(
							'taxonomy' => $term->taxonomy,
							'field' => 'term_id',
							'terms' => $term->term_id
						)
					);

				}

			}

			if ($items = get_posts($args)) {

				foreach ($items as $item) {

					tw_template_part($template, $item);

				}

			}

		}

	}

	exit();

}


function tw_ajax_load_button($wrapper, $template = 'post', $query = false, $number = false) {

	global $wp_query;

	if (empty($query) or !($query instanceof WP_Query)) {
		$query = $wp_query;
	}

	$max_page = intval($query->max_num_pages);

	if ($max_page > 1) {

		$posts_per_page = intval($query->query_vars['posts_per_page']);

		$max_offset = intval($query->found_posts);

		$paged = intval($query->query_vars['paged']);

		if ($paged == 0) {
			$paged = 1;
		}

		if ($number == false) {
			$number = $posts_per_page;
		}

		$offset = $paged * $posts_per_page;

		$term_id = 0;
		$search = '';
		$type = get_post_type($query->post);
		$object = $query->get_queried_object();

		if ($object instanceof WP_Term) {
			$term_id = $object->term_id;
		}

		if ($query->is_search()) {
			$search = get_search_query();
		}

		$args = array(
			'number' => $number,
			'offset' => $offset,
			'max' => $max_offset,
			'type' => $type,
			'object' => $term_id,
			'search' => $search,
			'wrapper' => $wrapper,
			'template' => $template
		);

		?>

		<div class="buttons">
			<div class="button" data-loader="<?php echo htmlspecialchars(json_encode($args), ENT_QUOTES, 'UTF-8'); ?>">Load More</div>
		</div>

		<?php

	}

}


/*
jQuery(function($){

	$('.button[data-loader]').each(function() {

		var button = $(this), data = button.data('loader');

		if (data && data.offset < data.max) {

			var offset = data.offset;

			var wrapper = $(data.wrapper).find('.items');

			if (wrapper.length === 1) {

				data.action = 'load_posts';
				data.noncer = template.nonce;

				button.click(function() {

					$.ajax({
						url: template.ajaxurl,
						type: 'post',
						dataType: 'html',
						data: data,
						success: function(response) {

							if (response) {

								var posts = $(response);

								posts.hide();

								wrapper.append(posts);

								posts.slideDown();

								offset = offset + data.number;

								data.offset = offset;

								if (offset >= data.max) {
									button.remove();
								}

							} else {

								button.remove();

							}

						}

					});

				})

			}

		} else {

			button.remove();

		}

	});

});

*/