<?php

echo tw_app_template('posts', [
	'block'    => $block ?? [],
	'type'     => 'post',
	'template' => 'post',
	'wrapper'  => 'posts_box',
]);