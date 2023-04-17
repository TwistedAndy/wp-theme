<?php

$options = [];

if (!empty($block['options']) and is_array($block['options'])) {
	$options = $block['options'];
}

?>
<section <?php echo tw_block_attributes('heading_box', $block); ?>>

	<div class="fixed">

		<?php echo tw_block_contents($block); ?>

	</div>

	<div class="background">
		<?php if (!empty($block['background'])) { ?>
			<?php echo tw_image($block['background'], 'full', '', ''); ?>
		<?php } ?>
	</div>

</section>