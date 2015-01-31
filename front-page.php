<?php get_header(); ?>

<?php the_post(); ?>

<div class="content">

	<h1><?php echo tw_wp_title(); ?></h1>
	
	<?php the_content(); ?>

</div>

<?php get_footer(); ?>