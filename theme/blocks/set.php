<?php if (!empty($block['set'])) {

	$current_id = get_queried_object_id();

	if (!is_array($block['set'])) {
		$block['set'] = [$block['set']];
	}

	$block['set'] = array_map('absint', $block['set']);

	foreach ($block['set'] as $set) {
		echo tw_get_blocks('blocks', $set);
	}

}