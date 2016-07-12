<?php get_header(); ?>

<?php if (!have_posts()) { ?>

	<?php get_template_part('parts/none'); ?>

<?php } else { ?>
	
	<?php get_template_part('parts/category'); ?>

<?php } ?>

<?php get_footer(); ?>