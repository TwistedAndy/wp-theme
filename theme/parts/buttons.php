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

	$classes = ['button'];

	if (!empty($size)) {
		$classes[] = $size;
	}

	if (!empty($button['type']) and $button['type'] !== 'default') {
		$classes[] = $button['type'];
	}

	if (!empty($button['icon']) and $button['icon'] !== 'none') {
		$classes[] = 'button_' . $button['icon'];
	}

	echo tw_content_link($button['link'], implode(' ', $classes));

}

if (!empty($wrapper)) {
	echo '</div>';
}