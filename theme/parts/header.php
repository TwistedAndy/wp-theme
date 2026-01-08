<?php

tw_asset_enqueue('header_box');

$classes = ['header_box', 'is_sticky'];

$blocks = tw_app_get('current_blocks', null);

if (!is_array($blocks)) {

	$object = get_queried_object();

	if ($object instanceof WP_Post) {
		$blocks = tw_meta_get('post', $object->ID, 'blocks');
	} elseif ($object instanceof WP_Term) {
		$blocks = tw_meta_get('term', $object->term_id, 'blocks');
	} else {
		$blocks = [];
	}

}

if ($blocks and is_array($blocks)) {

	$block = reset($blocks);

	if ($block and !empty($block['options']) and in_array('transparent', $block['options'])) {
		$classes[] = 'is_transparent';
	}

}

?>
<header class="<?php echo implode(' ', $classes); ?>">

	<div class="fixed">

		<a href="<?php echo get_site_url(); ?>" class="logo" aria-label="<?php echo esc_attr(get_bloginfo('name', 'display')); ?>"></a>

		<nav role="navigation" class="navigation">

			<?php if (has_nav_menu('main')) { ?>
				<?php wp_nav_menu([
					'items_wrap' => '<ul id="%1$s" class="%2$s" role="menubar" aria-label="Main Navigation">%3$s</ul>',
					'theme_location' => 'main',
					'container' => '',
					'container_class' => '',
					'menu_class' => 'menu',
					'menu_id' => 'main-menu'
				]); ?>
			<?php } ?>

		</nav>

		<button class="menu_btn" aria-label="Toggle Menu" aria-expanded="true" aria-controls="main-menu"><span></span></button>

		<form action="<?php echo esc_url(home_url('/')); ?>" method="get" role="search">
			<input type="search" value="<?php echo get_search_query(); ?>" placeholder="Search" name="s" role="searchbox" />
			<input type="submit" value="<?php echo esc_attr_x('Search', 'submit button'); ?>" />
		</form>

	</div>

</header>