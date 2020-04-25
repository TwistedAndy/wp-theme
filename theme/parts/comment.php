<div class="inner">

	<div class="photo"><?php echo get_avatar($comment, 60); ?></div>

	<div class="contents">

		<div class="heading">
			<span class="author"><?php echo get_comment_author_link(get_comment_ID()); ?></span>
			<span class="date"><?php echo get_comment_date('d.m.Y, H:i', get_comment_ID()); ?></span>
		</div>

		<div class="content">

			<?php comment_text(get_comment_ID()); ?>

			<?php if ($comment->comment_approved == '0') { ?>
				<p class="comment_on_moderation"><?php echo __('This comment is not approved yet.', 'wp-theme'); ?></p>
			<?php } ?>

		</div>

		<div class="buttons">
			<?php comment_reply_link(array_merge($args, array('depth' => $depth, 'max_depth' => $args['max_depth']))); ?>
			<?php edit_comment_link(); ?>
		</div>

	</div>

</div>