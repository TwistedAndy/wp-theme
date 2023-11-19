<?php

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

tw_app_set('current_blocks', $blocks);

get_header();

echo tw_get_blocks($blocks);

get_footer();