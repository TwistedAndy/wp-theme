<?php echo tw_wp_title('<h1>', '</h1>'); ?>

<?php if (have_posts()) { ?>

	<?php while (have_posts()) { the_post(); ?>

		<?php echo tw_template_part('post', get_post()); ?>

	<?php } ?>

	<?php echo tw_pagination(array('type' => 'posts')); ?>

<?php } else { ?>

	<div class="content">

		<p><?php echo __('No posts found.'); ?></p>

	</div>

<?php } ?>