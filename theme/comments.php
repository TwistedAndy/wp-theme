<?php if (post_password_required()) return; ?>

<div id="comments">

	<?php if (have_comments()) { ?>

		<div class="heading_box">Комментарии</div>

		<div class="comments_box">

			<?php wp_list_comments(array('callback' => 'tw_comment', 'style' => 'div', 'format' => 'xhtml')); ?>

		</div>

		<?php echo tw_pagination(array('type' => 'comments')); ?>

	<?php } ?>


	<?php

	$commenter = wp_get_current_commenter();

	$user = wp_get_current_user();

	if ($user->exists()) {
		$user_name = $user->display_name;
	} else {
		$user_name = '';
	}

	$login_text = sprintf(__('You need to <a href="%s">login</a> to post a comment.', 'wp-theme'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id))));

	$logout_text = sprintf(__('You\'ve logged in as <a class="login" href="%1$s">%2$s</a> <a class="logout" href="%3$s">[log out]</a>', 'wp-theme'), get_edit_user_link(), $user_name, wp_logout_url(apply_filters('the_permalink', get_permalink($post_id))));

	comment_form(array(
		'fields' => array(
			'author' => '<div class="fields"><div class="field"><input placeholder="Ваше имя" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" required="required" /></div>',
			'email' => '<div class="field"><input placeholder="Контактная почта" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30" required="required" /></div></div>'
		),
		'comment_field' => '<textarea id="comment" name="comment" cols="45" rows="8" required="required" placeholder="Ваш комментарий"></textarea>',
		'must_log_in' => '<p class="must-log-in">' . $login_text . '</p>',
		'logged_in_as' => '<p class="logged-in-as">' . $logout_text . '</p>',
		'label_submit' => 'Комментировать',
		'title_reply' => 'Добавить комментарий',
		'title_reply_before' => '<div id="reply-title" class="comment-reply-title">',
		'title_reply_after' => '</div>',
		'submit_field' => '<div class="buttons form-submit">%1$s %2$s</div>',
	)); ?>

</div>