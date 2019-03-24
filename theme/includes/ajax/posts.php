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

	if (!empty($_REQUEST['template']) and in_array($_REQUEST['template'], array('post'))) {
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

		if ($params['object'] > 0 and !empty($_REQUEST['taxonomy']) and taxonomy_exists($_REQUEST['taxonomy'])) {

			$args['tax_query'] = array(
				array(
					'taxonomy' => $_REQUEST['taxonomy'],
					'field' => 'term_id',
					'terms' => $params['object']
				)
			);

		}

		if ($items = get_posts($args)) {

			foreach ($items as $item) {

				tw_template_part($template, $item);

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

		wp_enqueue_script('jquery');

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
		$taxonomy = 0;
		$search = '';
		$type = get_post_type($query->post);
		$object = get_queried_object();

		if ($object instanceof WP_Term) {

			$term_id = $object->term_id;

			$taxonomy = $object->projects;

		} elseif (is_post_type_archive() or is_single()) {

			$taxonomy = tw_post_taxonomy();

		} elseif (is_search()) {

			$search = get_search_query();

		}

		?>

		<span class="more">Загрузить ещё</span>

		<script type="text/javascript">

			jQuery(function($){

				var offset = <?php echo $offset; ?>,
					max_offset = <?php echo $max_offset; ?>,
					number = <?php echo $number; ?>,
					wrapper = $('<?php echo $wrapper; ?>'),
					button = $('.more'),
					posts;

				button.click(function() {

					if (offset < max_offset) {

						$.ajax({
							type: "POST",
							data: {
								action: 'load_posts',
								offset: offset,
								number: number,
								template: '<?php echo $template; ?>',
								object: <?php echo $term_id; ?>,
								taxonomy: '<?php echo $taxonomy; ?>',
								search: '<?php echo $search; ?>',
								type: '<?php echo $type; ?>'
							},
							url: '<?php echo admin_url('admin-ajax.php'); ?>',
							dataType: 'html',
							success: function(data) {
								if (data) {
									posts = $(data);
									posts.hide();
									wrapper.append(posts);
									posts.slideDown();
									offset = offset + number;
									if (offset >= max_offset) {
										button.remove();
									}
								} else {
									button.remove();
								}
							}
						});

					} else {

						button.remove();

					}

				});

			});

		</script>

		<?php

	}

}