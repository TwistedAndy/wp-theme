<?php

$blocks = [];

$blocks[] = [
	'acf_fc_layout' => 'message',
	'image' => 'pic_locked.svg',
	'contents' => [
		'title' => __('Page not found', 'twee'),
		'tag' => 'h2',
		'text' => __('Sorry, but the page you were trying to view does not exist.', 'twee'),
		'buttons' => [
			[
				'type' => 'default',
				'icon' => '',
				'link' => [
					'url' => get_site_url(),
					'icon' => 'none',
					'title' => __('Back to homepage', 'twee')
				]
			]
		]
	],
	'settings' => [
		'background' => 'default'
	]
];

get_header();

echo tw_block_render($blocks);

get_footer();