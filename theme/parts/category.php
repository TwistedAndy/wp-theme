<?php echo tw_wp_title('<h1>', '</h1>'); ?>

<?php if (have_posts()) { ?>

	<?php while (have_posts()) { the_post(); ?>

		<div class="post">

			<?php echo tw_thumb(false, 'post', '<div class="thumb">', '</div>'); ?>

			<div class="body">
				<a class="title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				<p><?php echo tw_text(false, 400); ?></p>
			</div>

		</div>

	<?php } ?>

	<?php echo tw_pagination(array('type' => 'posts')); ?>

<?php } else { ?>

	<div class="content">

		<p><?php echo tw_not_found_text(); ?></p>

	</div>

<?php } ?>