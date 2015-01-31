<?php

/*
Описание: библиотека для работы с AJAX
Автор: Тониевич Андрей
Версия: 1.5
Дата: 18.01.2015
*/

if (tw_settings('init', 'ajax_rating')) {

	add_action('wp_ajax_nopriv_post-rating', 'post_rating');
	add_action('wp_ajax_post-rating', 'post_rating');

	function post_rating(){ 
		
		$nonce = $_POST['nonce'];  
	   
		if (!wp_verify_nonce($nonce, 'ajax-nonce')) exit();
		  
		if (isset($_POST['rating_vote'])) {
		  
			$timebeforerevote = 120;
			
			$ip = $_SERVER['REMOTE_ADDR'];
			$post_id = intval($_POST['post_id']);  
			$meta_IP = get_post_meta($post_id, "rating_IP");
			
			if (is_array($meta_IP) and isset($meta_IP[0])) $rating_IP = $meta_IP[0]; else $rating_IP = array();
			
			if (in_array($ip, array_keys($rating_IP))) {
				$time = $rating_IP[$ip];  
				$now = time();
				if (round(($now - $time) / 60) < $timebeforerevote) {
					echo json_encode(array('error' => 'Вы уже голосовали'));
					exit();
				}
			}
			
			$rating_vote = intval($_POST['rating_vote']);
			if ($rating_vote > 5 or $rating_vote < 0) exit();
			
			$rating_value = get_post_meta($post_id, "rating_value", true);
			if (empty($rating_value)) {
				delete_post_meta($post_id, "rating_value");
				add_post_meta($post_id, "rating_value", '0');
				$rating_value = 0;
			}
		
			$rating_votes = get_post_meta($post_id, "rating_votes", true);
			if (empty($rating_votes)) {
				delete_post_meta($post_id, "rating_votes");
				add_post_meta($post_id, "rating_votes", '0');
				$rating_votes = 0;
			}
			
			$rating_sum = get_post_meta($post_id, "rating_sum", true);
			if (empty($rating_sum)) {
				delete_post_meta($post_id, "rating_sum");
				add_post_meta($post_id, "rating_sum", '0');
				$rating_sum = 0;
			}
	  
			$rating_IP[$ip] = time();  
			
			$rating_sum = $rating_sum + $rating_vote;
			
			$rating_votes++;
			
			$rating_value =  round($rating_sum/$rating_votes);
			
			update_post_meta($post_id, "rating_IP", $rating_IP);
			update_post_meta($post_id, "rating_votes", $rating_votes);
			update_post_meta($post_id, "rating_value", $rating_value);
			update_post_meta($post_id, "rating_sum", $rating_sum);
			
			$result = array(
				'rating' => intval($rating_value),
				'votes' => intval($rating_votes)
			);
			
			echo json_encode($result);  
	  
		}
		
		exit();  

	}

}


if (tw_settings('init', 'ajax_posts')) {

	add_action('wp_ajax_nopriv_load_posts', 'tw_load_posts');
	add_action('wp_ajax_load_posts', 'tw_load_posts');

	function tw_load_posts(){

		$fields = array('category', 'tag', 'search', 'offset', 'number');
		
		foreach ($fields as $field) {
			if (isset($_REQUEST[$field])) $$field =	htmlspecialchars($_REQUEST[$field]); else $$field = '';
		}
		
		if ($number) {
			
			if ($items = get_posts(array('numberposts' => $number, 'offset' => $offset, 'category' => $category, 'tag_id' => $tag, 's' => $search))) { ?>

				<?php foreach ($items as $item) { ?>
					
				<div class="post">
					<?php echo tw_thumb($item, 'post', '<div class="thumb">', '</div>'); ?>
					<div class="post_body">
						<a class="title" href="<?php echo get_permalink($item->ID); ?>"><?php echo tw_title($item); ?></a>
						<p><?php echo tw_text($item, 400); ?></p>
					</div>
				</div>
				
				<?php } ?>

			<?php }
		
		}
		
		exit();
		
	}

	function tw_load_button($load_posts_number = 6, $ignore_max_page = false){
		
		global $wp_query;
		
		$max_page = intval($wp_query->max_num_pages);
		
		if ($ignore_max_page or $max_page > 1) {
		
			wp_enqueue_script('jquery');
			
			$posts_per_page = intval(get_query_var('posts_per_page'));  
			
			$paged = intval(get_query_var('paged'));
			 
			if ($paged == 0) $paged = 1; 
			
			$offset = $paged * $posts_per_page;
			
			$max_offset = intval($wp_query->found_posts);
			
			?>
			
			<span class="more">Загрузить ещё</span>
			
			<script type="text/javascript">
				
				var offset = <?php echo $offset; ?>;
				var max_offset = <?php echo $max_offset; ?>;
				var more_button = jQuery('.more');
			
				more_button.click(function(){
					
					if (offset < max_offset) {
						
						jQuery.ajax({
							type: "POST",
							data: {
								action: 'load_posts',
								category: '<?php echo get_query_var('cat'); ?>',
								tag: '<?php echo get_query_var('tag_id'); ?>',
								search: '<?php echo get_query_var('s'); ?>',
								offset: offset,
								number: <?php echo $load_posts_number; ?>
							},
							url: '<?php echo admin_url('admin-ajax.php'); ?>',
							dataType: 'html',
							success: function(data){
								if (data) {
									var el = jQuery(data);
									el.hide();
									more_button.before(el);
									el.slideDown();
									offset = offset + <?php echo $load_posts_number; ?>;
									if (offset >= max_offset) {
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

			</script>
			
			<?php
					
		}

	}

}


if (tw_settings('init', 'ajax_comments')) {

	add_action('wp_ajax_nopriv_load_comments', 'tw_load_comments');
	add_action('wp_ajax_load_comments', 'tw_load_comments');

	function tw_load_comments(){
		
		if (isset($_POST['post_id'])) {
					
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

}

/*

<?php $page = get_query_var('cpage');

if (!$page) $page = 1;

$max_page = get_comment_pages_count(); ?>

<?php if ($max_page > 1) { ?>

<div class="pages">
	<?php for ($i = 1; $i <= $max_page; $i++) { ?>
	<span<?php if ($i == $max_page) { ?> class="active"<?php } ?>><?php echo $i; ?></span>
	<?php } ?>
</div>

<script type="text/javascript">

$(function(){
	
	$('#comments .pages span').each(function(i){
		
		var page = i+1;
		var el = $(this);
		el.click(function(){
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
						el.addClass('active').siblings().removeClass('active');
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


<script type="text/javascript">

var rating = parseInt(<?php echo $rating; ?>);

jQuery('.rating > span').each(function(i){
	
	var num = i+1;
	
	jQuery(this).click(function(){

		jQuery.ajax({
			type: "POST",
			data: {
				action: 'post-rating',
				rating_vote: num,
				nonce: '<?php echo wp_create_nonce('ajax-nonce'); ?>',
				post_id: '<?php the_ID(); ?>'
			},
			url: '<?php echo admin_url('admin-ajax.php'); ?>',
			dataType: 'json',
			success: function(data){
				
				if (data.error) alert(data.error);
				
				if (data.rating) {
					
					rating = data.rating;
					
					jQuery('.rating > span').removeClass('active');
					jQuery('.rating > span:lt(' + parseInt(data.rating) + ')').addClass('active');
				}
				
			}
		});
		
	});

	jQuery(this).hover(
		function(){
			jQuery('.rating > span').removeClass('active');
			jQuery('.rating > span:lt(' + num + ')').addClass('active');	
		},
		function(){
			
		}
	);
	
});
jQuery('.rating').hover(
	function(){
	},
	function(){
		jQuery('.rating > span').removeClass('active');
		jQuery('.rating > span:lt(' + rating + ')').addClass('active');	
	}
);

</script>
*/

?>
