<?php
/**
 * Comment library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */


/**
 * Build a comment list with the load more button
 *
 * @param string $type
 *
 * @return void
 */
function tw_comment_list($type = 'post') {

	if (!have_comments()) {
		return;
	}

	if (!in_array($type, ['review', 'comment'])) {
		$type = 'comment';
	}

	$page = get_query_var('cpage');
	$pages = get_comment_pages_count();

	$args = [
		'callback' => function($comment, $args, $depth) use ($type) {

			$GLOBALS['comment'] = $comment;

			echo '<div id="comment-' . get_comment_ID() . '" class="' . join(' ', get_comment_class('comment')) . '">';

			if ('div' != $args['style']) {
				echo '<div id="div-comment-' . get_comment_ID() . '">';
			}

			$data = ['comment' => $comment, 'args' => $args, 'depth' => $depth, 'type' => $type];

			echo tw_app_template('comment', $data);

			if ('div' != $args['style']) {
				echo '</div>';
			}

		},
		'style' => 'div',
		'format' => 'xhtml'
	];

	echo '<div class="comments" id="comments">';

	wp_list_comments($args);

	$data = [
		'type' => $type,
		'post' => get_the_ID(),
		'page' => $page,
		'pages' => $pages
	];

	$loader = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');

	echo '</div>';

	if ($pages > 1) {
		echo '<div class="buttons"><button type="button" aria-label="' . esc_attr__('Load More', 'twee') . '" class="button outline" data-comments="' . $loader . '">' . __('Load More', 'twee') . '</button></div>';
	}

}


/**
 * Callback function to build a single comment
 *
 * @param WP_Comment  $comment
 * @param array       $args
 * @param int         $depth
 *
 * @global WP_Comment $comment
 *
 */
function tw_comment_item($comment, $args, $depth) {

	$GLOBALS['comment'] = $comment;

	echo '<div id="comment-' . get_comment_ID() . '" class="' . join(' ', get_comment_class()) . '">';

	if ('div' != $args['style']) {
		echo '<div id="div-comment-' . get_comment_ID() . '">';
	}

	echo tw_app_template('comment', ['comment' => $comment, 'args' => $args, 'depth' => $depth]);

	if ('div' != $args['style']) {
		echo '</div>';
	}

}


/**
 * Move the comment message field to the bottom and
 * add a class to the cookies consent field
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