<section <?php echo tw_block_attributes('content_box', $block); ?>>

	<div class="fixed">

		<?php echo tw_block_contents($block); ?>

		<?php if (!empty($block['text'])) { ?>
			<div class="content">
				<?php echo $block['text']; ?>
				<?php tw_asset_enqueue('fancybox'); ?>
			</div>
		<?php } ?>

	</div>

</section>