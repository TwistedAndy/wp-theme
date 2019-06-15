<?php

get_header();

the_post();

if (!is_front_page()) {
	the_title('<div class="fixed"><h1>', '</h1></div>');
}

tw_get_blocks('blocks');

if (get_the_content()) {

?>

<div class="content_box">

	<div class="fixed">

		<div class="content">

			<?php the_content(); ?>

		</div>

	</div>

</div>

<?php }

get_footer();