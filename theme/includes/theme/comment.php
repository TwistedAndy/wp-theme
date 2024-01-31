<?php
/**
 * Comment Library:
 * - Add a comment using AJAX
 * - Load more comments
 * - Adjust a comment form
 * - Custom comment templates
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */


/**
 * Load additional comments using AJAX
 */
add_action('wp_ajax_nopriv_comment_list', 'tw_ajax_comment_list');
add_action('wp_ajax_comment_list', 'tw_ajax_comment_list');

function tw_ajax_comment_list() {

	if (isset($_POST['noncer']) and wp_verify_nonce($_POST['noncer'], 'ajax-nonce')) {

		$fields = ['post', 'page'];

		$params = [];

		foreach ($fields as $field) {
			if (isset($_REQUEST[$field])) {
				$params[$field] = intval($_REQUEST[$field]);
			} else {
				$params[$field] = 0;
			}
		}

		if ($params['page'] > 0 and $params['post'] > 0) {

			query_posts([
				'p' => $params['post'],
				'post_type' => 'product'
			]);

			the_post();

			set_query_var('cpage', $params['page']);

			comments_template();

		}

	}

}


/**
 * Post comment using AJAX
 */
add_action('wp_ajax_nopriv_comment_add', 'wp_ajax_comment_create');
add_action('wp_ajax_comment_add', 'wp_ajax_comment_create');

function wp_ajax_comment_create() {

	$result = [
		'text' => '',
		'link' => '',
		'errors' => []
	];

	if (!empty($_POST['comment_post_ID'])) {
		$post = get_post($_POST['comment_post_ID']);
	} else {
		$post = false;
	}

	if ($post instanceof WP_Post and isset($_POST['noncer']) and wp_verify_nonce($_POST['noncer'], 'ajax-nonce')) {

		$user = wp_get_current_user();

		$data = [
			'url' => '',
			'author' => '',
			'email' => '',
			'comment' => '',
			'comment_parent' => 0,
			'comment_post_ID' => $post->ID
		];

		$fields = [
			'comment' => [
				'error' => 'Please enter a comment',
				'pattern' => '#.{10,}#ui'
			]
		];

		if (empty($user->ID)) {

			$fields['author'] = [
				'error' => 'Enter your name',
				'pattern' => '#^[a-zA-Z0-9 -.]{2,}$#ui'
			];

			$fields['email'] = [
				'error' => 'Enter your email',
				'pattern' => '#^[^\@]+@.*\.[a-z]{2,6}$#i'
			];

		}

		foreach ($fields as $k => $v) {
			if (isset($_POST[$k]) and !preg_match($v['pattern'], $_POST[$k]) and !(isset($v['empty']) and $v['empty'] and $_POST[$k] == '')) {
				$result['errors'][$k] = $v['error'];
			}
		}

		if (count($result['errors']) === 0) {

			foreach ($data as $key => $value) {
				if (!empty($_POST[$key])) {
					$data[$key] = $_POST[$key];
				}
			}

			$comment = wp_handle_comment_submission($data);

			if ($comment instanceof WP_Error) {

				$result['errors']['comment'] = $comment->get_error_message();

			} elseif ($comment instanceof WP_Comment) {

				$result['text'] = __('Thank you! You review will be published shortly!', 'twee');
				$result['link'] = get_permalink($post) . '#comment-' . $comment->comment_ID;

			}

		}

	} else {

		$result['errors']['comment'] = __('<strong>Error</strong>: Post is not valid', 'twee');

	}

	wp_send_json($result);

	exit();

}


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