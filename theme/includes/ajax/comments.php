<?php
/**
 * Load the comments list with pagination
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

/*
add_action('wp_ajax_nopriv_load_comments', 'tw_ajax_load_comments');
add_action('wp_ajax_load_comments', 'tw_ajax_load_comments');
*/

function tw_ajax_load_comments() {

	if (!empty($_POST['post_id']) and !empty($_POST['cpage']) and !empty($_POST['nonce']) and wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {

		global $wp_query;

		$wp_query = new WP_Query(array(
			'p' => intval($_POST['post_id'])
		));

		if (have_posts()) {
			set_query_var('cpage', intval($_POST['cpage']));
			the_post();
			comments_template('/ajax-comments.php');
		}

	}

	exit();

}

/*

<?php

$page = get_query_var('cpage');

if (empty($page)) {
	$page = 1;
}

$max_page = get_comment_pages_count();

if ($max_page > 1) { ?>

	<div class="pages">
		<?php for ($i = 1; $i <= $max_page; $i++) { ?>
		<span<?php echo ($i == $max_page) ? ' class="active"' : ''; ?>><?php echo $i; ?></span>
		<?php } ?>
	</div>

	<script type="text/javascript">

		jQuery(function($){

			$('#comments .pages span').each(function(i){

				var page = i+1;
				var element = $(this);
				element.click(function(){
					$.ajax({
						type: "POST",
						data: {
							action: 'load_comments',
							cpage: page,
							nonce: '<?php echo wp_create_nonce('ajax-nonce'); ?>',
							post_id: '<?php the_ID(); ?>'
						},
						url: '<?php echo admin_url('admin-ajax.php'); ?>',
						dataType: 'html',
						success: function(data){
							if (data) {
								element.addClass('active').siblings().removeClass('active');
								$('.commentlist').fadeOut(300, function() {
									$(this).html(data).fadeIn(300);
								});
							}
						}
					});
				});

			});

		});

	</script>

<?php } ?>

*/