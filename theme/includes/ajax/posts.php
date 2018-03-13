<?php
/**
 * Load more posts in the list
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

add_action('wp_ajax_nopriv_load_posts', 'tw_load_posts');
add_action('wp_ajax_load_posts', 'tw_load_posts');

function tw_load_posts() {

	$fields = array('category', 'tag', 'search', 'offset', 'number');
	$params = array();

	foreach ($fields as $field) {
		if (isset($_REQUEST[$field])) {
			$params[$field] = htmlspecialchars($_REQUEST[$field]);
		} else {
			$params[$field] = '';
		}
	}

	if ($params['number']) {

		$args = array(
			'numberposts' => $params['number'],
			'offset' => $params['offset'],
			'category' => $params['category'],
			'tag_id' => $params['tag'],
			's' => $params['search']
		);

		if ($items = get_posts($args)) { ?>

			<?php foreach ($items as $item) { ?>

				<div class="post">
					<?php echo tw_thumb($item, 'post', '', '', array('link' => 'url', 'link_class' => 'thumb')); ?>
					<div class="text">
						<a class="title" href="<?php echo get_permalink($item->ID); ?>"><?php echo tw_title($item); ?></a>
						<p><?php echo tw_text($item, 400); ?></p>
					</div>
				</div>

			<?php } ?>

		<?php }

	}

	exit();

}

function tw_load_button($load_posts_number = false, $ignore_max_page = false) {

	global $wp_query;

	$max_page = intval($wp_query->max_num_pages);

	if ($ignore_max_page or $max_page > 1) {

		wp_enqueue_script('jquery');

		$posts_per_page = intval(get_query_var('posts_per_page'));

		$max_offset = intval($wp_query->found_posts);

		$paged = intval(get_query_var('paged'));

		if ($paged == 0) {
			$paged = 1;
		}

		if ($load_posts_number == false) {
			$load_posts_number = $posts_per_page;
		}

		$offset = $paged * $posts_per_page;

		?>

		<span class="more">Загрузить ещё</span>

		<script type="text/javascript">

			jQuery(function($){

				var offset = <?php echo $offset; ?>,
					number = <?php echo $load_posts_number; ?>,
					max_offset = <?php echo $max_offset; ?>,
					more_button = $('.more'),
					el;

				more_button.click(function() {

					if (offset < max_offset) {

						$.ajax({
							type: "POST", data: {
								action: 'load_posts',
								category: '<?php echo get_query_var('cat'); ?>',
								tag: '<?php echo get_query_var('tag_id'); ?>',
								search: '<?php echo get_query_var('s'); ?>',
								offset: offset,
								number: number
							}, url: '<?php echo admin_url('admin-ajax.php'); ?>', dataType: 'html', success: function(data) {
								if (data) {
									el = $(data);
									el.hide();
									more_button.before(el);
									el.slideDown();
									offset = offset + number;
									if (offset >= max_offset || number == -1) {
										more_button.remove();
									}
								} else {
									more_button.remove();
								}
							}
						});

					} else {

						more_button.remove();

					}

				});

			});

		</script>

		<?php

	}

}