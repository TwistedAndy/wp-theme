<?php

get_header();

$object = get_queried_object();

$blocks = get_field('blocks', $object);

if (empty($blocks)) {

	if ($object instanceof WP_Term) {

		$title = $object->name;
		$text = $object->description;

	} else {

		$title = tw_wp_title();
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

echo tw_get_blocks($blocks);

get_footer();