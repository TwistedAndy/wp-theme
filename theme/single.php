<?php

get_header();

the_post();

tw_asset_enqueue('fancybox');

?>

<section class="content_box">

	<div class="fixed">

		<div class="content">

			<?php the_title('<h1>', '</h1>'); ?>

			<?php the_content(); ?>

		</div>

	</div>

</section>

<?php if (comments_open() or get_comments_number()) comments_template(); ?>

<?php get_footer(); ?>