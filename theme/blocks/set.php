<?php if (!empty($block['set'])) {

	$current_id = get_queried_object_id();

	if (!is_array($block['set'])) {
		$block['set'] = [$block['set']];
	}

	$block['set'] = array_map('absint', $block['set']);

	foreach ($block['set'] as $set) {

		$blocks = get_field('blocks', $set);

		if ($blocks and is_array($blocks)) {
			echo tw_block_render($blocks);
		}

	}

}