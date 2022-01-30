<?php if (!empty($block['set']) and get_the_ID() != $block['set']) {

	echo tw_get_blocks('blocks', $block['set']);

}