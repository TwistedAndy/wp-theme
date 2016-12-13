<?php get_header(); ?>

<?php the_post(); ?>

	<div class="content">

		<?php the_title('<h1>', '</h1>'); ?>

		<?php the_content(); ?>

	</div>

<?php echo tw_pagination(array('type' => 'page')); ?>

<?php if (comments_open() or get_comments_number()) comments_template(); ?>

<?php get_footer(); ?>