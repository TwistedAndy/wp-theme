<?php

/*
Описание: библиотека для работы с AJAX
Автор: Тониевич Андрей
Версия: 1.6
Дата: 19.01.2016
*/

if (tw_settings('init', 'ajax_mail')) {
	
	add_action('wp_ajax_nopriv_send_email', 'tw_send_email');
	add_action('wp_ajax_send_email', 'tw_send_email');
	
	function tw_send_email(){
	
		if (isset($_POST['nonce']) and wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
		
			$errors = array();

			foreach($_POST as $k => $v) {
				
				$_POST[$k] = htmlspecialchars($v);
				
			}
			
			if (isset($_POST['email'])) {
			
				$fields = array(
					'name'	=> array(
						'error'	=> 'Неверно указано имя',
						'pattern'	=> '#^[a-zA-Zа-яА-Я0-9 -.]{2,}$#ui'
					),
					'email'	=> array(
						'error'	=> 'Неверно указан e-mail',
						'pattern' => '#^[^\@]+@.*\.[a-z]{2,6}$#i'
					),
					'message'	=> array(
						'error'	=> 'Введите сообщение',
						'pattern'	=> '#^.{4,}$#i'
					)
				);
				
				$is_callback = false;
				
			} else {
				
				$fields = array(
					'name'	=> array(
						'error'	=> 'Неверно указано имя',
						'pattern'	=> '#^[a-zA-Zа-яА-Я0-9 -.]{2,}$#ui'
					),
					'phone'	=> array(
						'error'	=> 'Неверно указан телефон',
						'pattern'	=> '#^[0-9 +\- ()]{4,}$#i'
					)
				);
				
				$is_callback = true;
				
			}


			foreach ($fields as $k => $v) {
				
				if (isset($_POST[$k]) and !preg_match($v['pattern'], $_POST[$k]) and !(isset($v['empty']) and $v['empty'] and $_POST[$k] == '')) {
					
					$errors[$k] = $v['error'];
					
				}

			}

			if (count($errors) == 0) {

				$to = get_option('admin_email');
				
				if ($is_callback) {
				
					$subject = "Заказ обратного звонка от " . $_POST['name'] . " (" . $_POST['phone'] . ")";
					$message = "
					<p><b>Имя:</b> " . $_POST['name'] . "</p>
					<p><b>Телефон:</b> ". $_POST['phone'] ."</p>";
					
					$_POST['email'] = $to;
					
				} else {
					
					$subject = "Сообщение от посетителя";
					$message = "
					<p><b>Имя:</b> " . $_POST['name'] . "</p>
					<p><b>E-mail:</b> " . $_POST['email'] . "</p>
					<p><b>Сообщение:</b> " . $_POST['message'] . "</p>";
					
				}
				 
				$headers  = "Content-type: text/html; charset=utf-8 \r\n"; 
				$headers .= "From: " . $_POST['name'] . " <" . $_POST['email'] . ">\r\n";

				if (mail($to, $subject, $message, $headers)) {

					echo (json_encode(array('text' => "Ваш запрос был успешно отправлен")));     
				 
				} else { 

					echo(json_encode(array('text' => "Ошибка. Запрос не отправлен из-за ошибки сервера")));     
				 
				}
				
			} else {
				
				echo(json_encode(array('errors' => $errors)));     
				
			}
		
		}
		
		exit();
		
	}

	/*

	<form action="<?php echo admin_url('admin-ajax.php'); ?>" method="post">
		<input type="text" value="" placeholder="Как вас зовут" name="name" />
		<input type="text" value="" placeholder="Ваш e-mail" name="email" />
		<textarea cols="40" rows="5" placeholder="Сообщение" name="message"></textarea>
		<input type="submit" value="Отправить" />
		<input type="hidden" name="action" value="send_email" />
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ajax-nonce'); ?>" />
	</form>

	<script type="text/javascript">
				
	jQuery(function($){
		
		$('form').submit(function(e){

			var form = $(this), el;

			$.ajax({
				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				type: 'post',
				dataType: 'json',
				data: $('input:text, input:hidden, input:checked, textarea, select', form),
				success: function(data) {
				
					$('.error', form).remove();	
					
					$('input, textarea, select', form).removeClass('incorrect');

					if (data['errors']) {
						for (i in data['errors']) {
							el = $('<div class="error">' + data['errors'][i] + '</div>');
							$('[name=' + i + ']', form).addClass('incorrect').after(el);
							el.hide();
							el.slideDown();
						}
					}

					if (data['text']) {
						el = $('<div class="success">' + data['text'] + '</div>');
						form.append(el);
						el.hide();
						el.slideDown();
						$('input[type="text"], textarea, select', form).val('');
					}

				}
			});

			e.preventDefault();

			return false;

		});

	});

	</script>

	*/
	
}

	
if (tw_settings('init', 'ajax_rating')) {

	add_action('wp_ajax_nopriv_post_rating', 'tw_post_rating');
	add_action('wp_ajax_post_rating', 'tw_post_rating');

	function tw_post_rating(){ 
		
		if (isset($_POST['rating_vote']) and isset($_POST['nonce']) and wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
		  
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
			
			$rating_value = round($rating_sum/$rating_votes);
			
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
	
	/*
	
	<?php
	
	$rating = get_post_meta(get_the_ID(), 'rating_value', true);
	
	if (empty($rating)) {
		delete_post_meta(get_the_ID(), 'rating_value');
		add_post_meta(get_the_ID(), 'rating_value', '0');
		$rating = 0;
	}
	
	?>
	
	<div class="rating">

		<?php for ($i = 0; $i < 4; $i++) { ?>

		<span<?php if ($rating > $i) echo ' class="active"'; ?>></span>

		<?php } ?>

	</div>
	
	<script type="text/javascript">

	jQuery(function($){
	
		var rating = parseInt('<?php echo $rating; ?>');

		$('.rating > span').each(function(i){
			
			var num = i+1;
			
			$(this).click(function(){

				$.ajax({
					type: "POST",
					data: {
						action: 'post_rating',
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
							
							$('.rating > span').removeClass('active');
							$('.rating > span:lt(' + parseInt(data.rating) + ')').addClass('active');
						}
						
					}
				});
				
			});

			$(this).hover(
				function(){
					$('.rating > span').removeClass('active');
					$('.rating > span:lt(' + num + ')').addClass('active');	
				},
				function(){
					
				}
			);
			
		});
		
		$('.rating').hover(
			function(){
			},
			function(){
				$('.rating > span').removeClass('active');
				$('.rating > span:lt(' + rating + ')').addClass('active');	
			}
		);
	
	});

	</script>
	
	*/

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

	function tw_load_button($load_posts_number = false, $ignore_max_page = false){
		
		global $wp_query;
		
		$max_page = intval($wp_query->max_num_pages);
		
		if ($ignore_max_page or $max_page > 1) {
		
			wp_enqueue_script('jquery');
			
			$posts_per_page = intval(get_query_var('posts_per_page'));  
			
			$max_offset = intval($wp_query->found_posts);
			
			$paged = intval(get_query_var('paged'));
			
			if ($paged == 0) $paged = 1; 
			
			if ($load_posts_number == false) $load_posts_number = $posts_per_page;
			 
			$offset = $paged * $posts_per_page;
			
			?>
			
			<span class="more">Загрузить ещё</span>
			
			<script type="text/javascript">
				
				var offset = <?php echo $offset; ?>;
				var number = <?php echo $load_posts_number; ?>;
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
								number: number
							},
							url: '<?php echo admin_url('admin-ajax.php'); ?>',
							dataType: 'html',
							success: function(data){
								if (data) {
									var el = jQuery(data);
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

			</script>
			
			<?php
					
		}

	}

}


if (tw_settings('init', 'ajax_comments')) {

	add_action('wp_ajax_nopriv_load_comments', 'tw_load_comments');
	add_action('wp_ajax_load_comments', 'tw_load_comments');

	function tw_load_comments(){
		
		if (isset($_POST['post_id']) and isset($_POST['nonce']) and wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
					
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

<?php

$page = get_query_var('cpage');

if (!$page) $page = 1;

$max_page = get_comment_pages_count(); 

if ($max_page > 1) { ?>

<div class="pages">
	<?php for ($i = 1; $i <= $max_page; $i++) { ?>
	<span<?php if ($i == $max_page) { ?> class="active"<?php } ?>><?php echo $i; ?></span>
	<?php } ?>
</div>

<script type="text/javascript">

jQuery(function($){
	
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

*/

?>
