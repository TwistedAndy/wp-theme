<?php

if (comments_open($post_id)) {

	do_action('comment_form_before');

	echo '<div id="respond" class="comment-respond">';

		echo $args['title_reply_before'] . $args['title_reply'] . $args['title_reply_after'];

		if (get_option('comment_registration') && !is_user_logged_in()) {

			echo $args['must_log_in'];

			do_action('comment_form_must_log_in_after');

		} else {

			echo '<form action="' . site_url('/wp-comments-post.php') . '" method="post" id="' . $args['id_form'] . '" class="' . $args['class_form'] . '">';

				do_action('comment_form_top');

				if (is_user_logged_in()) {

					echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_name);

					do_action('comment_form_logged_in_after', $commenter, $user_name);

				} else {

					do_action('comment_form_before_fields');

					$comment_fields = apply_filters('comment_form_fields', $args['fields']);

					foreach ($comment_fields as $name => $field) {
						echo apply_filters("comment_form_field_{$name}", $field) . "\n";
					}

					do_action('comment_form_after_fields');

				}

				echo $args['comment_notes_before'] . $args['comment_field'] . $args['comment_notes_after'];

				echo $args['submit_field'];

				do_action('comment_form', $post_id);

			echo '</form>';

		}

	echo '</div>';

	do_action('comment_form_after');

} else {

	echo '<div id="respond" class="comment-respond">';

		echo '<p class="nocomments">' . __('Sorry, but comments are closed.', 'wp-theme') . '</p>';

	echo '</div>';

	do_action('comment_form_comments_closed');

}