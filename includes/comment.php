<?php
/**
 * Comment functions
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 1.8
 */


/**
 * Callback function to build a single comment
 *
 * @global WP_Comment $comment
 *
 * @param $comment WP_Comment
 * @param array $args
 * @param int $depth
 */

function tw_comment($comment, $args, $depth) {

	$GLOBALS['comment'] = $comment;

	echo '<div id="comment-' . get_comment_ID() . '" class="' . join(' ', get_comment_class()) . '">';

	if ('div' != $args['style']) {
		echo '<div id="div-comment-' . get_comment_ID() . '">';
	}

	tw_get_template_part('comment', array('comment' => $comment, 'args' => $args, 'depth' => $depth));

	if ('div' != $args['style']) {
		echo '</div>';
	}

}


/**
 * Outputs a complete commenting form. It is similar to default comment_form() function
 *
 * @param array $args
 * @param int $post_id
 */

function tw_comment_form($args = array(), $post_id = 0) {

	if ($post_id == 0) {
		$post_id = get_the_ID();
	}

	$user = wp_get_current_user();

	if ($user->exists()) {
		$user_name = $user->display_name;
	} else {
		$user_name = '';
	}

	$login_text = sprintf(__('You need to <a href="%s">login</a> to post a comment.', 'wp-theme'), wp_login_url(apply_filters('the_permalink', get_permalink($post_id))));

	$logout_text = sprintf(__('You\'ve logged in as <a class="login" href="%1$s">%2$s</a> <a class="logout" href="%3$s">[log out]</a>', 'wp-theme'), get_edit_user_link(), $user_name, wp_logout_url(apply_filters('the_permalink', get_permalink($post_id))));

	if (get_option('require_name_email')) {
		$require_email = ' required="required"';
	} else {
		$require_email = '';
	}

	$commenter = wp_get_current_commenter();
	$commenter_author = esc_attr($commenter['comment_author']);
	$commenter_email = esc_attr($commenter['comment_author_email']);
	$commenter_url = esc_attr($commenter['comment_author_url']);

	$fields = apply_filters('comment_form_default_fields', array(
		'author' => '<input type="text" name="author" value="' . $commenter_author . '" placeholder="' . __('Your name', 'wp-theme') . '" required="required" />',
		'email' => '<input type="text" name="email" value="' . $commenter_email . '" placeholder="' . __('Your e-mail', 'wp-theme') . '"' . $require_email . ' />',
		'url' => '<input type="text" name="url" value="' . $commenter_url . '" placeholder="' . __('Site', 'wp-theme') . '" />',
	));

	$defaults = array(
		'fields' => $fields,
		'fields_before' => '',
		'fields_after' => '',
		'comment_field' => '<textarea id="comment" name="comment" required="required"></textarea>',
		'comment_notes_before' => '',
		'comment_notes_after' => '',
		'textarea_first' => false,
		'must_log_in' => '<p class="must-log-in">' . $login_text . '</p>',
		'logged_in_as' => '<p class="logged-in-as">' . $logout_text . '</p>',
		'id_form' => 'commentform',
		'id_submit' => 'submit',
		'class_form' => 'comment-form',
		'class_submit' => 'submit',
		'name_submit' => 'submit',
		'title_reply' => __('Leave a Reply', 'wp-theme'),
		'title_reply_to' => __('Leave a Reply to %s', 'wp-theme'),
		'title_reply_before' => '<div id="reply-title" class="comment-reply-title">',
		'title_reply_after' => '</div>',
		'cancel_reply_before' => ' <small>[',
		'cancel_reply_after' => ']</small>',
		'cancel_reply_link' => __('Cancel reply', 'wp-theme'),
		'label_submit' => __('Post Comment', 'wp-theme'),
		'submit_button' => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
		'submit_field' => '<p class="form-submit">%1$s %2$s</p>',
		'format' => 'xhtml',
		'disabled_message' => true
	);

	$args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

	if (empty($_GET['replytocom'])) {
		$title = $args['title_reply_before'] . $args['title_reply'] . $args['title_reply_after'];
	} else {
		$author = '<a href="#comment-' . get_comment_ID() . '">' . get_comment_author(get_comment(intval($_GET['replytocom']))) . '</a>';
		$title = $args['title_reply_before'] . sprintf($args['title_reply_to'], $author);
		$title .= $args['cancel_reply_before'] . get_cancel_comment_reply_link($args['cancel_reply_link']) . $args['cancel_reply_after'] . $args['title_reply_after'];
	}

	$textarea = apply_filters('comment_form_fields', array('comment' => $args['comment_field']));
	$textarea = apply_filters('comment_form_field_comment', $textarea['comment']);
	$textarea = $args['comment_notes_before'] . $textarea . $args['comment_notes_after'];

	$submit_button = apply_filters('comment_form_submit_field', sprintf($args['submit_field'], apply_filters('comment_form_submit_button', sprintf($args['submit_button'], $args['name_submit'], $args['id_submit'], $args['class_submit'], $args['label_submit']), $args), get_comment_id_fields($post_id)), $args);

	if (comments_open($post_id)) {

		do_action('comment_form_before');

		echo '<div id="respond" class="comment-respond">';

		echo $title;

		if (get_option('comment_registration') && !is_user_logged_in()) {

			echo $args['must_log_in'];

			do_action('comment_form_must_log_in_after');

		} else {

			echo '<form action="' . site_url('/wp-comments-post.php') . '" method="post" id="' . $args['id_form'] . '" class="' . $args['class_form'] . '">';

			do_action('comment_form_top');

			if ($args['textarea_first']) {
				echo $textarea;
			}

			if (is_user_logged_in()) {

				echo apply_filters('comment_form_logged_in', $args['logged_in_as'], $commenter, $user_name);

				do_action('comment_form_logged_in_after', $commenter, $user_name);

			} else {

				echo $args['fields_before'];

				do_action('comment_form_before_fields');

				$comment_fields = apply_filters('comment_form_fields', $args['fields']);

				foreach ($comment_fields as $name => $field) {
					echo apply_filters("comment_form_field_{$name}", $field) . "\n";
				}

				do_action('comment_form_after_fields');

				echo $args['fields_after'];

			}

			if (!$args['textarea_first']) {
				echo $textarea;
			}

			echo $submit_button;

			do_action('comment_form', $post_id);

			echo '</form>';

		}

		echo '</div>';

		do_action('comment_form_after');

	} else {

		if ($args['disabled_message']) { ?>

			<div id="respond" class="comment-respond">

				<p class="nocomments"><?php echo __('Sorry, but comments are closed.', 'wp-theme'); ?></p>

			</div>

		<?php }

		do_action('comment_form_comments_closed');

	}

}