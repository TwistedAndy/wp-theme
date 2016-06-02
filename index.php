<?php get_header(); ?>

<?php if (!have_posts()) { ?>

	<?php get_template_part('content/none'); ?>

<?php } else { ?>

	<?php get_template_part('content/category'); ?>

<?php } ?>

<?php get_footer(); ?>
