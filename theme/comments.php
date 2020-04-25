<?php if (post_password_required()) return; ?>

<section class="comments_box">

	<?php if (have_comments()) { ?>

		<h2>Comments</h2>

		<div class="comments">
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
			'author' => '<input placeholder="' . __('Name') . '" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" required />',
			'email' => '<input placeholder="' . __('Email') . '" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" required />'
		),
		'comment_field' => '<textarea id="comment" name="comment" required="required" placeholder="' . __('Comment', 'noun') . '" maxlength="65525"></textarea>',
		'must_log_in' => '<p class="must-log-in">' . $login_text . '</p>',
		'logged_in_as' => '<p class="logged-in-as">' . $logout_text . '</p>',
		'label_submit' => __('Post Comment'),
		'title_reply' => __('Leave a Reply'),
		'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
		'title_reply_after' => '</h2>',
		'comment_notes_before' => '',
		'submit_field' => '<div class="form-submit">%1$s %2$s</div>',
	));

	?>

</section>



