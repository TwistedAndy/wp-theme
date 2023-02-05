<?php
/**
 * Post comment using AJAX
 *
 * @author  Andrii Toniievych <andy@absoluteweb.com>
 * @package Twee
 * @version 3.0
 */

/*
add_action('wp_ajax_nopriv_comment', 'wp_ajax_comment');
add_action('wp_ajax_comment', 'wp_ajax_comment');
*/

function wp_ajax_comment() {

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

		$data = array(
			'url' => '',
			'author' => '',
			'email' => '',
			'comment' => '',
			'comment_parent' => 0,
			'comment_post_ID' => $post->ID
		);

		$fields = array(
			'comment' => array(
				'error' => 'Please enter comment',
				'pattern' => '#.{10,}#ui'
			)
		);

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