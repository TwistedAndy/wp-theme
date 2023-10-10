<?php

if (post_password_required()) {
	return;
}

$number = get_comments_number();

?>
<section class="comments_box">

	<div class="fixed">

		<?php if (have_comments()) { ?>

			<h3><?php printf(_n('%s comment', '%s comments', $number), $number) ?></h3>

			<?php tw_comment_list('post'); ?>

		<?php } ?>

		<?php

		$commenter = wp_get_current_commenter();

		$user = wp_get_current_user();

		$post = get_queried_object();
		$post_id = get_queried_object_id();

		if ($user->exists()) {
			$user_name = $user->display_name;
		} else {
			$user_name = '';
		}

		$number = get_comments_number($post);

		$login_text = sprintf(__('You need to <a href="%s">login</a> to post a comment.', 'twee'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id))));

		$logout_text = sprintf(__('You\'ve logged in as <a class="login" href="%1$s">%2$s</a> <a class="logout" href="%3$s">[log out]</a>', 'twee'), get_edit_user_link(), $user_name, wp_logout_url(apply_filters('the_permalink', get_permalink($post_id))));

		comment_form([
			'fields' => [
				'author' => '<input placeholder="' . __('Name') . '" aria-label="' . __('Name') . '" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" required />',
				'email' => '<input placeholder="' . __('Email') . '" aria-label="' . __('Email') . '" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" required />'
			],
			'comment_field' => '<textarea id="comment" name="comment" required="required" placeholder="' . __('Comment', 'noun') . '" maxlength="65525"></textarea>',
			'must_log_in' => '<p class="must-log-in">' . $login_text . '</p>',
			'logged_in_as' => '<p class="logged-in-as">' . $logout_text . '</p>',
			'label_submit' => __('Post Comment'),
			'title_reply' => __('Leave a Reply'),
			'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
			'title_reply_after' => '</h3>',
			'comment_notes_before' => '',
			'class_submit' => 'submit button',
			'submit_field' => '<div class="form-submit">%1$s %2$s</div>',
		]);

		?>

	</div>

</section>