<?php

/*
Описание: библиотека для работы с комментариями
Автор: Тониевич Андрей
Версия: 1.6
Дата: 18.01.2016
*/


function tw_comment($comment, $args, $depth ) {

	$GLOBALS['comment'] = $comment; ?>

	<div id="comment-<?php comment_ID(); ?>" <?php comment_class(); ?>>

		<?php if ('div' != $args['style']) echo '<div id="div-comment-' . get_comment_ID() . '">'; ?>

		<div class="comment_inner">

			<div class="comment_avatar"><?php echo get_avatar($comment, 60); ?></div>

			<div class="comment_body">

				<div class="comment_info">
					<span class="comment_author"><?php echo get_comment_author_link(get_comment_ID()); ?></span>
					<span class="comment_date"><?php echo get_comment_date('d.m.Y в H:i', get_comment_ID()); ?></span>
				</div>

				<?php comment_text(get_comment_ID()); ?>

				<?php if ($comment->comment_approved == '0') { ?>
					<div class="comment_on_moderation">Комментарий ожидает модерации.</div>
				<?php } ?>

				<div class="comment_buttons">
					<?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))); ?>
					<?php edit_comment_link(); ?>
				</div>

			</div>

		</div>

		<?php if ('div' != $args['style']) echo '</div>';

}




function tw_comment_form($args = array(), $post_id = false) {

	if ($post_id == false) $post_id = get_the_ID();

	$commenter = wp_get_current_commenter();
	$user = wp_get_current_user();
	$user_identity = $user->exists() ? $user->display_name : '';

	$args = wp_parse_args($args);

	$fields = array(
		'author' => '<input placeholder="Ваше имя..." name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" required="required" />',
		'email'  => '<input placeholder="Ваш email..." name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30"' . (get_option('require_name_email') ? ' required="required"' : '') . ' />',
		'url'    => '<input placeholder="Адрес сайта..." name="url" type="text" value="' . esc_attr($commenter['comment_author_url']) . '" size="30" />',
	);

	$fields = apply_filters('comment_form_default_fields', $fields);

	$defaults = array(
		'fields'               => $fields,
		'comment_field'        => '<textarea id="comment" name="comment" cols="45" rows="8" required="required"></textarea>',
		'must_log_in'          => '<p class="must-log-in">' . sprintf('Вы должны <a href="%s">войти</a>, чтобы оставить комментарий.', wp_login_url(apply_filters('the_permalink', get_permalink($post_id)))) . '</p>',
		'logged_in_as'         => '<p class="logged-in-as">' . sprintf('Вы вошли как <a class="login" href="%1$s">%2$s</a> <a class="logout" href="%3$s">[выйти]</a>', get_edit_user_link(), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink($post_id)))) . '</p>',
		'comment_notes_before' => '',
		'comment_notes_after'  => '',
		'id_form'              => 'commentform',
		'id_submit'            => 'submit',
		'class_form'           => 'comment-form',
		'class_submit'         => 'submit',
		'name_submit'          => 'submit',
		'title_reply'          => __( 'Leave a Reply' ),
		'title_reply_to'       => __( 'Leave a Reply to %s' ),
		'title_reply_before'   => '<div id="reply-title" class="comment-reply-title">',
		'title_reply_after'    => '</div>',
		'cancel_reply_before'  => ' <small>[',
		'cancel_reply_after'   => ']</small>',
		'cancel_reply_link'    => __( 'Cancel reply' ),
		'label_submit'         => __( 'Post Comment' ),
		'submit_button'        => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
		'submit_field'         => '<p class="form-submit">%1$s %2$s</p>',
		'format'               => 'xhtml',
		'disabled_message'	   => true
	);

	$args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

	$args = array_merge($defaults, $args);

	if (comments_open($post_id)) { ?>

		<?php do_action( 'comment_form_before' ); ?>

		<div id="respond" class="comment-respond">

			<?php

			echo $args['title_reply_before'];

			comment_form_title($args['title_reply'], $args['title_reply_to']);

			if (isset($_GET['replytocom'])) echo $args['cancel_reply_before'] . get_cancel_comment_reply_link($args['cancel_reply_link']) . $args['cancel_reply_after'];

			echo $args['title_reply_after'];

			if (get_option('comment_registration') && !is_user_logged_in()) {

				echo $args['must_log_in'];

				do_action( 'comment_form_must_log_in_after' );

			} else { ?>

				<form action="<?php echo site_url('/wp-comments-post.php'); ?>" method="post" id="<?php echo esc_attr($args['id_form']); ?>" class="<?php echo esc_attr($args['class_form']); ?>">

					<?php

					do_action('comment_form_top');

					if (is_user_logged_in()) {

						echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_identity);

						do_action('comment_form_logged_in_after', $commenter, $user_identity);

					} else {

						echo $args['comment_notes_before'];

						do_action('comment_form_before_fields');

						$comment_fields = apply_filters('comment_form_fields', $args['fields']);

						foreach ($comment_fields as $name => $field) {
							echo apply_filters("comment_form_field_{$name}", $field) . "\n";
						}

						do_action('comment_form_after_fields');

					}

					$textarea_field = apply_filters('comment_form_fields', array('comment' => $args['comment_field']));
					$textarea_field = apply_filters('comment_form_field_comment', $textarea_field['comment']);
					echo $textarea_field;

					$submit_button = sprintf($args['submit_button'], esc_attr($args['name_submit']), esc_attr($args['id_submit']), esc_attr($args['class_submit']), esc_attr($args['label_submit']));
					$submit_button = apply_filters('comment_form_submit_button', $submit_button, $args);
					$submit_field = sprintf($args['submit_field'], $submit_button, get_comment_id_fields($post_id));
					$submit_field = apply_filters( 'comment_form_submit_field', $submit_field, $args );
					echo $submit_field;

					do_action( 'comment_form', $post_id );

					?>

				</form>

			<?php } ?>

		</div>

		<?php

		do_action('comment_form_after');

	} else {

		if ($args['disabled_message']) { ?>

		<div id="respond" class="comment-respond">
			<p class="nocomments">Комментирование данной записи отключено</p>
		</div>

		<?php }

		do_action('comment_form_comments_closed');

	}

}