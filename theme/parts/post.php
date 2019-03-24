<?php if (!empty($item) and $item instanceof WP_Post) { ?>

	<div class="post">

		<?php echo tw_thumb($item, 'post', '', '', array('link' => 'url', 'link_class' => 'thumb')); ?>

		<div class="body">
			<a class="title" href="<?php echo get_permalink($item); ?>"><?php echo tw_title($item); ?></a>
			<div class="text"><?php echo tw_text($item, 400); ?></div>
		</div>

	</div>

<?php } ?>