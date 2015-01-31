<?php

/*
Описание: библиотека для работы с комментариями
Автор: Тониевич Андрей
Версия: 1.5
Дата: 18.01.2015
*/

function tw_comment($comment, $args, $depth ) {
	
	$GLOBALS['comment'] = $comment; ?>
	
	<div id="comment-<?php comment_ID(); ?>" class="comment"<?php echo (($depth > 1) ? (' style="margin-left:' . $depth * 35 . 'px"') : ''); ?>>
		
		<?php if ('div' != $args['style']) echo '<div id="div-comment-' . get_comment_ID() . '">'; ?>

		<div class="comment_inner">
		
			<div class="comment_avatar"><?php if ($args['avatar_size'] != 0) echo get_avatar($comment, 60); ?></div>
			
			<div class="comment_body">
			
				<div class="comment_info">
					<span class="comment_author"><?php echo get_comment_author_link($comment); ?></span>
					<span class="comment_date"><?php echo get_comment_date('d.m.Y', get_comment_ID()); ?></span>
				</div>

				<?php comment_text(get_comment_ID()); ?>
				
				<?php if ($comment->comment_approved == '0') { ?>
					<div class="comment-awaiting-moderation">Комментарий ожидает модерации.</div>
				<?php } ?>
				
				<div class="answer">
					<?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))); ?> <?php echo edit_comment_link(); ?>
				</div>
			
			</div>
			
		</div>
			
		<?php if ('div' != $args['style']) echo '</div>';

}


function tw_comment_form($args = array(), $post_id = null) {
	
	if (null === $post_id) {
		$post_id = get_the_ID();
	} else {
		$id = $post_id;
	}
		
	$commenter = wp_get_current_commenter();
	$user = wp_get_current_user();
	$user_identity = $user->exists() ? $user->display_name : '';

	$args = wp_parse_args($args);
	
	if (!isset($args['format'])) $args['format'] = current_theme_supports( 'html5', 'comment-form' ) ? 'html5' : 'xhtml';

	$req      = get_option( 'require_name_email' );
	
	$fields   =  array(
		'author' => '<input placeholder="Ваше имя..." name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" />',
		'email'  => '<input placeholder="Ваш email..." name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30" />',
		'url'    => '<input placeholder="Адрес сайта..." name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" />',
	);

	$required_text = sprintf(' ' . __('Required fields are marked %s'), '<span class="required">*</span>');

	$fields = apply_filters('comment_form_default_fields', $fields);

	$defaults = array(
		'fields'               => $fields,
		'comment_field'        => '<textarea id="comment" name="comment" cols="45" rows="8"></textarea>',
		'must_log_in'          => '<p class="must-log-in">' . sprintf(__('You must be <a href="%s">logged in</a> to post a comment.'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id)))) . '</p>',
		'logged_in_as'         => '<p class="logged-in-as">' . sprintf(__('Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>'), get_edit_user_link(), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink( $post_id )))) . '</p>',
		'comment_notes_before' => '<p class="comment-notes">' . __('Your email address will not be published.') . ($req ? $required_text : '') . '</p>',
		'comment_notes_after'  => '<p class="form-allowed-tags">' . sprintf(__('You may use these <abbr title="HyperText Markup Language">HTML</abbr> tags and attributes: %s'), ' <code>' . allowed_tags() . '</code>') . '</p>',
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'title_reply'          => __( 'Leave a Reply' ),
		'title_reply_to'       => __( 'Leave a Reply to %s' ),
		'cancel_reply_link'    => __( 'Cancel reply' ),
		'label_submit'         => __( 'Post Comment' ),
		'format'               => 'xhtml',
	);
	
	$args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

	if (comments_open($post_id)) { ?>
		
		<?php do_action('comment_form_before');?>
		
		<div id="respond" class="comment-respond">
			
			<h3 id="reply-title" class="comment-reply-title"><?php comment_form_title($args['title_reply'], $args['title_reply_to']); ?> <small><?php cancel_comment_reply_link($args['cancel_reply_link']); ?></small></h3>
			
			<?php if (get_option( 'comment_registration' ) && !is_user_logged_in()) {
				
				echo $args['must_log_in'];
				
				do_action('comment_form_must_log_in_after');
				
			} else { ?>
				
				<form action="<?php echo site_url( '/wp-comments-post.php' ); ?>" method="post" id="<?php echo esc_attr( $args['id_form'] ); ?>" class="comment-form">
					
					<?php do_action('comment_form_top');

					echo apply_filters('comment_form_field_comment', $args['comment_field']); /* Вывод textarea */
					
					if (is_user_logged_in()) {
						
						echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity );
						
						do_action('comment_form_logged_in_after', $commenter, $user_identity);
						
					} else {
						
						echo $args['comment_notes_before'];
						
						do_action('comment_form_before_fields');
						
						foreach ((array) $args['fields'] as $name => $field) {
							echo apply_filters("comment_form_field_{$name}", $field) . "\n";
						}
						
						do_action('comment_form_after_fields');
					
					}
					
					echo $args['comment_notes_after']; ?>
					
					<input name="submit" type="submit" id="<?php echo esc_attr($args['id_submit']); ?>" value="<?php echo esc_attr($args['label_submit']); ?>" />
					
					<?php comment_id_fields($post_id); ?>
					
					<?php do_action('comment_form', $post_id); ?>
					
				</form>
				
			<?php } ?>
			
		</div>
		
		<?php
	
		do_action( 'comment_form_after' );
			
	} else {
	
		do_action( 'comment_form_comments_closed' );
			
	}
	
}

?>
