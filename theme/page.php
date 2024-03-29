<?php

the_post();

$object = get_queried_object();

if (post_password_required($object)) {

	$blocks = [
		[
			'acf_fc_layout' => 'message',
			'image' => 'pic_locked.svg',
			'password' => true,
			'contents' => [
				'title' => __('Access Restricted', 'twee'),
				'tag' => 'h1',
				'text' => __('This content is password protected. To view it please enter your password below:', 'twee'),
				'buttons' => []
			]
		]
	];

} else {

	$blocks = get_field('blocks', $object);

	if (empty($blocks) and $object instanceof WP_Post) {

		$blocks = [
			[
				'acf_fc_layout' => 'heading',
				'contents' => [
					'title' => $object->post_title,
					'tag' => 'h1',
					'text' => $object->post_excerpt
				],
				'settings' => [
					'background' => 'light'
				]
			],
			[
				'acf_fc_layout' => 'content',
				'contents' => [],
				'text' => apply_filters('the_content', $object->post_content),
				'settings' => [
					'background' => 'default'
				]
			]
		];

	}

}

tw_app_set('current_blocks', $blocks);

get_header();

echo tw_block_render($blocks);

get_footer();