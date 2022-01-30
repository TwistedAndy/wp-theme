<?php

if (empty($buttons) or !is_array($buttons)) {
	return;
}

if (!empty($wrapper)) {
	echo '<div class="' . esc_attr($wrapper) . '">';
}

foreach ($buttons as $button) {

	if (!empty($button['code'])) {
		echo $button['code'];
		continue;
	}

	if (empty($button['link']) or !is_array($button['link'])) {
		continue;
	}

	$type = '';
	$target = '';
	$classes = ['button'];
	$link = $button['link'];

	if (!empty($size)) {
		$classes[] = $size;
	}

	if (!empty($link['target'])) {
		$target = ' target="' . $link['target'] . '"';
	}

	if (!empty($button['type']) and $button['type'] !== 'default') {
		$type = $button['type'];
		$classes[] = $button['type'];
	}

	if (!empty($button['icon']) and $button['icon'] !== 'none') {
		$classes[] = 'button_' . $button['icon'];
	}

	echo '<a href="' . esc_attr($link['url']) . '" class="' . implode(' ', $classes) . '"' . $target . '>' . $link['title'] . '</a>';

}

if (!empty($wrapper)) {
	echo '</div>';
}