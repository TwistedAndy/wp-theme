<div class="comment_inner">

	<div class="comment_avatar"><?php echo get_avatar($comment, 60); ?></div>

	<div class="comment_body">

		<div class="comment_info">
			<span class="comment_author"><?php echo get_comment_author_link(get_comment_ID()); ?></span>
			<span class="comment_date"><?php echo get_comment_date('d.m.Y, H:i', get_comment_ID()); ?></span>
		</div>

		<?php comment_text(get_comment_ID()); ?>

		<?php if ($comment->comment_approved == '0') { ?>
			<div class="comment_on_moderation"><?php echo __('This comment is not approved yet.', 'wp-theme'); ?></div>
		<?php } ?>

		<div class="comment_buttons">
			<?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))); ?>
			<?php edit_comment_link(); ?>
		</div>

	</div>

</div>