<?php

get_header();

if (!have_posts()) {
	
	get_template_part('parts/none');

} else { the_post(); ?>

	<div class="content">

		<?php the_title('<h1>', '</h1>'); ?>

		<?php the_content(); ?>

	</div>

	<?php echo tw_pagination(array('type' => 'page')); ?>

<?php }

get_footer();