<?php
/**
 * Rate post
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.0
 */

add_action('wp_ajax_nopriv_post_rating', 'tw_post_rating');
add_action('wp_ajax_post_rating', 'tw_post_rating');

function tw_post_rating() {

	if (isset($_POST['rating_vote']) and isset($_POST['nonce']) and wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {

		$timebeforerevote = 120;

		$ip = $_SERVER['REMOTE_ADDR'];
		$post_id = intval($_POST['post_id']);
		$meta_IP = get_post_meta($post_id, 'rating_IP');

		if (is_array($meta_IP) and isset($meta_IP[0])) {
			$rating_IP = $meta_IP[0];
		} else {
			$rating_IP = array();
		}

		if (in_array($ip, array_keys($rating_IP))) {
			$time = $rating_IP[$ip];
			$now = time();
			if (round(($now - $time) / 60) < $timebeforerevote) {
				echo json_encode(array('error' => 'Вы уже голосовали'));
				exit();
			}
		}

		$rating_vote = intval($_POST['rating_vote']);
		if ($rating_vote > 5 or $rating_vote < 0) {
			exit();
		}

		$rating_value = get_post_meta($post_id, 'rating_value', true);
		if (empty($rating_value)) {
			delete_post_meta($post_id, 'rating_value');
			add_post_meta($post_id, 'rating_value', '0');
			$rating_value = 0;
		}

		$rating_votes = get_post_meta($post_id, 'rating_votes', true);
		if (empty($rating_votes)) {
			delete_post_meta($post_id, 'rating_votes');
			add_post_meta($post_id, 'rating_votes', '0');
			$rating_votes = 0;
		}

		$rating_sum = get_post_meta($post_id, 'rating_sum', true);
		if (empty($rating_sum)) {
			delete_post_meta($post_id, 'rating_sum');
			add_post_meta($post_id, 'rating_sum', '0');
			$rating_sum = 0;
		}

		$rating_IP[$ip] = time();

		$rating_sum = $rating_sum + $rating_vote;

		$rating_votes++;

		$rating_value = round($rating_sum / $rating_votes, 3);

		update_post_meta($post_id, 'rating_IP', $rating_IP);
		update_post_meta($post_id, 'rating_votes', $rating_votes);
		update_post_meta($post_id, 'rating_value', $rating_value);
		update_post_meta($post_id, 'rating_sum', $rating_sum);

		$result = array(
			'rating' => round($rating_value, 0),
			'votes' => intval($rating_votes)
		);

		echo json_encode($result);

	}

	exit();

}

/*

<?php
$rating = get_post_meta(get_the_ID(), 'rating_value', true);
if (empty($rating)) {
	delete_post_meta(get_the_ID(), 'rating_value');
	add_post_meta(get_the_ID(), 'rating_value', '0');
	$rating = 0;
} else {
	$rating = round($rating, 0);
}
?>
<div class="rating" data-id="<?php the_ID(); ?>">
	<?php for ($i = 0; $i < 5; $i++) { ?>
	<span<?php if ($rating > $i) echo ' class="active"'; ?>></span>
	<?php } ?>
</div>

jQuery(function($){

	$('.rating').each(function(){

		var rating = parseInt($('span.active', this).length);
		var post_id = parseInt($(this).data('id'));
		var element = $(this);

		$('span', this).each(function(i){

			var num = i+1;

			$(this).click(function(){
				$.ajax({
					type: "POST",
					data: {
						action: 'post_rating',
						rating_vote: num,
						nonce: '<?php echo wp_create_nonce('ajax-nonce'); ?>',
						post_id: post_id
					},
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					dataType: 'json',
					success: function(data){
						if (data.error) alert(data.error);
						if (data.rating) {
							rating = data.rating;
							$('span', element).removeClass('active');
							$('span:lt(' + parseInt(data.rating) + ')', element).addClass('active');
						}
					}
				});
			});

			$(this).hover(
				function(){
					$('span', element).removeClass('active');
					$('span:lt(' + num + ')', element).addClass('active');
				},
				function(){

				}
			);

		});

		element.hover(
			function(){
			},
			function(){
				$('span', this).removeClass('active');
				$('span:lt(' + rating + ')', this).addClass('active');
			}
		);

	});

});

*/