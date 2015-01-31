<?php get_header(); ?>

<?php if (!have_posts()) { ?>

	<?php get_template_part('content', 'none'); ?>

<?php } else { ?>

	<?php while (have_posts()) { the_post(); ?>

	<div class="content">

		<h1><?php echo tw_wp_title(); ?></h1>
		
		<?php the_content(); ?>

	</div>
	
	<?php echo tw_navigation(array('type' => 'page')); ?>
		
	<?php } ?>

<?php } ?>

<?php get_footer(); ?>