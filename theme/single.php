<?php

get_header();

the_post();

tw_asset_enqueue('fancybox');

$object = get_queried_object();

$blocks = get_field('blocks', $object);

if (empty($blocks) and $object instanceof WP_Post) {

	$thumbnail_id = get_post_meta($object->ID, '_thumbnail_id', true);

	$blocks = [
		[
			'acf_fc_layout' => 'heading',
			'contents' => [
				'title' => $object->post_title,
				'tag' => 'h1',
				'text' => $object->post_excerpt
			],
			'background' => $thumbnail_id,
			'settings' => [
				'background' => 'dark'
			]
		],
		[
			'acf_fc_layout' => 'content',
			'contents' => [],
			'text' => apply_filters('the_content', $object->post_content),
			'settings' => [
				'background' => 'white'
			]
		]
	];

}

echo tw_get_blocks($blocks);

if (comments_open() or get_comments_number()) {
	comments_template();
}

get_footer();