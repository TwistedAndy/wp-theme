<?php

get_header();

the_post();

$blocks = get_field('blocks');

echo tw_get_blocks($blocks);

if (get_the_content()) { ?>

	<section class="content_box">

		<div class="fixed">

			<div class="content">

				<?php if (empty($blocks)) { ?>
					<?php the_title('<h1>', '</h1>'); ?>
				<?php } ?>

				<?php the_content(); ?>

			</div>

		</div>

	</section>

<?php }

get_footer();