<?php

if (empty($comment) or !($comment instanceof WP_Comment)) {
	return;
}

$title = get_comment_meta($comment->comment_ID, 'title', true);

if ($title and strpos(strtolower($comment->comment_content), strtolower($title)) === 0) {
	$title = '';
}

if (empty($depth)) {
	$depth = 0;
}

if (empty($args)) {
	$args = [];
}

if (!empty($type) and $type == 'review' and function_exists('woocommerce_review_display_rating')) {

	remove_action('woocommerce_review_before', 'woocommerce_review_display_gravatar');
	remove_action('woocommerce_review_before_comment_meta', 'woocommerce_review_display_rating');
	remove_action('woocommerce_review_comment_text', 'woocommerce_review_display_comment_text');

	?>

	<div class="inner">

		<?php do_action('woocommerce_review_before', $comment); ?>

		<div class="header">

			<div class="name">
				<?php echo get_comment_author($comment); ?>
			</div>

			<div class="date"><?php printf(__('%s ago'), human_time_diff(strtotime($comment->comment_date_gmt))); ?></div>

			<?php woocommerce_review_display_rating(); ?>

		</div>

		<?php do_action('woocommerce_review_before_comment_meta', $comment) ?>

		<?php if ($title) { ?>
			<div class="title"><?php echo $title; ?></div>
		<?php } ?>

		<div class="content">

			<?php do_action('woocommerce_review_before_comment_text', $comment); ?>

			<?php if ($comment->comment_approved === '0') { ?>
				<p class="comment_on_moderation"><?php esc_html_e('Your review is awaiting approval', 'woocommerce'); ?></p>
			<?php } else { ?>
				<?php comment_text($comment); ?>
			<?php } ?>

			<?php do_action('woocommerce_review_comment_text', $comment); ?>

			<?php do_action('woocommerce_review_after_comment_text', $comment); ?>

		</div>

		<?php if (is_user_logged_in()) { ?>
			<div class="links">
				<?php comment_reply_link(array_merge($args, ['depth' => $depth, 'max_depth' => $args['max_depth']])); ?>
				<?php edit_comment_link(); ?>
			</div>
		<?php } ?>

	</div>

<?php } else { ?>

	<div class="inner">

		<div class="header">

			<div class="name">
				<?php echo get_comment_author($comment); ?>
			</div>

			<div class="date"><?php printf(__('%s ago'), human_time_diff(strtotime($comment->comment_date_gmt))); ?></div>

		</div>

		<?php if ($title) { ?>
			<div class="title"><?php echo $title; ?></div>
		<?php } ?>

		<div class="content">
			<?php if ($comment->comment_approved === '0') { ?>
				<p class="comment_on_moderation"><?php esc_html_e('Your review is awaiting approval', 'woocommerce'); ?></p>
			<?php } else { ?>
				<?php comment_text($comment); ?>
			<?php } ?>
		</div>

		<?php if (is_user_logged_in()) { ?>
			<div class="links">
				<?php comment_reply_link(array_merge($args, ['depth' => $depth, 'max_depth' => $args['max_depth']])); ?>
				<?php edit_comment_link(); ?>
			</div>
		<?php } ?>

	</div>

<?php } ?>