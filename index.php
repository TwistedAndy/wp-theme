<?php

get_header();

if (!have_posts()) {

	get_template_part('parts/none');

} else {

	get_template_part('parts/category');

}

get_footer();