<?php get_header(); ?>

<?php the_post(); ?>

	<div class="content">

		<?php the_title('<h1>', '</h1>'); ?>

		<?php the_content(); ?>

	</div>

<?php get_footer(); ?>