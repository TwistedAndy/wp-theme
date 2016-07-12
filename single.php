<?php

get_header();

if (!have_posts()) {
	
	get_template_part('parts/none');

} else { the_post(); ?>

	<div class="content">

		<h1><?php echo tw_wp_title(); ?></h1>

		<?php the_content(); ?>

	</div>

	<?php echo tw_pagination(array('type' => 'page')); ?>

	<?php if (comments_open() or get_comments_number()) comments_template(); ?>
	
<?php }

get_footer();