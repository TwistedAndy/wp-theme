<?php

global $wp_query;

$object = get_queried_object();

$blocks = get_field('blocks', $object);

if (empty($blocks)) {

	$blocks = [];

	if ($object instanceof WP_Term) {

		$title = $object->name;
		$text = $object->description;

	} else {

		$title = tw_content_heading();
		$text = '';

	}

	if ($wp_query instanceof WP_Query and $wp_query->is_search() and empty($wp_query->posts)) {

		$blocks[] = [
			'acf_fc_layout' => 'message',
			'image' => 'pic_search.svg',
			'search' => true,
			'contents' => [
				'title' => __('No results found', 'twee'),
				'tag' => 'h2',
				'text' => __("Sorry, we couldn't find what you are looking for.", 'twee'),
			],
			'settings' => [
				'background' => 'default'
			]
		];

	} else {

		$blocks[] = [
			'acf_fc_layout' => 'posts',
			'contents' => [
				'title' => $title,
				'tag' => 'h1',
				'text' => $text
			],
			'options' => ['current'],
			'template' => 'post',
			'settings' => [
				'background' => 'default'
			]
		];

	}

}

tw_app_set('current_blocks', $blocks);

get_header();

echo tw_block_render($blocks);

get_footer();