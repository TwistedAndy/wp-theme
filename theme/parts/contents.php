<?php

if (empty($block) or !is_array($block)) {
	return;
}

if (empty($block['tag'])) {
	$block['tag'] = 'h2';
}

if (!empty($wrapper)) {
	if (!empty($block['layout']) and $block['layout'] !== 'default') {
		$wrapper .= ' is_' . $block['layout'];
	} elseif (!empty($split) and !empty($block['buttons'])) {
		$wrapper .= ' is_split';
	}
}

if (!empty($block['title']) or !empty($block['caption']) or !empty($block['text']) or !empty($block['buttons']) or !empty($block['before']) or !empty($block['after'])) { ?>

	<?php echo !empty($wrapper) ? '<div class="' . $wrapper . '">' : ''; ?>

	<?php if (!empty($block['before'])) { ?>
		<?php echo $block['before']; ?>
	<?php } ?>

	<?php if (!empty($block['caption'])) { ?>
		<div class="caption">
			<?php echo $block['caption']; ?>
		</div>
	<?php } ?>

	<?php if (!empty($block['title'])) { ?>
		<?php echo '<' . $block['tag'] . '>' . $block['title'] . '</' . $block['tag'] . '>'; ?>
	<?php } ?>

	<?php if (!empty($block['text'])) { ?>
		<div class="content">
			<?php echo $block['text']; ?>
		</div>
	<?php } ?>

	<?php if (!empty($block['middle'])) { ?>
		<?php echo $block['middle']; ?>
	<?php } ?>

	<?php if (!empty($block['buttons'])) { ?>
		<?php echo tw_block_buttons($block['buttons']); ?>
	<?php } ?>

	<?php if (!empty($block['after'])) { ?>
		<?php echo $block['after']; ?>
	<?php } ?>

	<?php echo !empty($wrapper) ? '</div>' : ''; ?>

<?php }