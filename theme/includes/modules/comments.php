<?php
/**
 * Comment library
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Callback function to build a single comment
 *
 * @global WP_Comment $comment
 *
 * @param WP_Comment  $comment
 * @param array       $args
 * @param int         $depth
 */

function tw_comment($comment, $args, $depth) {

	$GLOBALS['comment'] = $comment;

	echo '<div id="comment-' . get_comment_ID() . '" class="' . join(' ', get_comment_class()) . '">';

	if ('div' != $args['style']) {
		echo '<div id="div-comment-' . get_comment_ID() . '">';
	}

	$filename = TW_ROOT . '/parts/comment.php';

	if (is_file($filename)) {
		include($filename);
	}

	if ('div' != $args['style']) {
		echo '</div>';
	}

}


/**
 * Outputs a complete commenting form. It is similar to default comment_form() function
 *
 * @param array $args
 * @param int   $post_id
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
		'comment_field' => '<textarea id="comment" name="comment" required="required"></textarea>',
		'comment_notes_before' => '',
		'comment_notes_after' => '',
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
		'format' => 'xhtml'
	);

	$args = wp_parse_args($args, apply_filters('comment_form_defaults', $defaults));

	if (!empty($_GET['replytocom'])) {
		$comment_id = intval($_GET['replytocom']);
		$author = sprintf($args['title_reply_to'], '<a href="#comment-' . get_comment_ID() . '">' . get_comment_author(get_comment($comment_id)) . '</a>');
		$args['title_reply'] = $author . $args['cancel_reply_before'] . get_cancel_comment_reply_link($args['cancel_reply_link']) . $args['cancel_reply_after'];
	}

	$textarea = apply_filters('comment_form_fields', array('comment' => $args['comment_field']));

	$args['comment_field'] = apply_filters('comment_form_field_comment', $textarea['comment']);

	$submit_button = apply_filters('comment_form_submit_button', sprintf($args['submit_button'], $args['name_submit'], $args['id_submit'], $args['class_submit'], $args['label_submit']), $args);

	$args['submit_field'] = apply_filters('comment_form_submit_field', sprintf($args['submit_field'], $submit_button, get_comment_id_fields($post_id)), $args);

	$filename = TW_ROOT . '/parts/reply.php';

	if (is_file($filename)) {
		include($filename);
	}

}