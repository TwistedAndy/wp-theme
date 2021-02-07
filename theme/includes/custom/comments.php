<?php
/**
 * Comment library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
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

	echo tw_template_part('comment', ['comment' => $comment, 'args' => $args, 'depth' => $depth]);

	if ('div' != $args['style']) {
		echo '</div>';
	}

}


/**
 * Move the comment message field to the bottom and add a class to the cookies consent field
 */
add_filter('comment_form_fields', function($fields) {

	if (!empty($fields['comment'])) {

		$field = $fields['comment'];
		unset($fields['comment']);
		$fields['comment'] = $field;

		$field = $fields['cookies'];
		unset($fields['cookies']);
		$fields['cookies'] = $field;

	}

	if (!empty($fields['cookies'])) {
		$fields['cookies'] = str_replace('comment-form-cookies-consent', 'comment-form-cookies-consent checkbox', $fields['cookies']);
	}

	foreach ($fields as $key => $field) {
		$fields[$key] = '<div class="field field_' . $key . '">' . $field . '</div>';
	}

	return $fields;

});


/*
 * Add a wrapper for comment fields
 */
add_action('comment_form_before_fields', function() {
	echo '<div class="fields">';
});

add_action('comment_form_after_fields', function() {
	echo '</div>';
});