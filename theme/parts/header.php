<header class="header_box">

	<div class="fixed">

		<a href="<?php echo get_site_url(); ?>" class="logo" aria-label="<?php echo esc_attr(get_bloginfo('name', 'display')); ?>"></a>

		<div class="navigation">

			<?php if (has_nav_menu('main')) { ?>
				<?php wp_nav_menu([
					'items_wrap' => '<ul id="%1$s" class="%2$s" role="menu" aria-label="Main Navigation">%3$s</ul>',
					'theme_location' => 'main',
					'container' => '',
					'container_class' => '',
					'menu_class' => 'menu',
					'menu_id' => 'main-menu'
				]); ?>
			<?php } ?>

		</div>

		<button class="menu_btn" aria-label="Toggle Menu" aria-expanded="true" aria-controls="main-menu"><span></span></button>

		<form action="<?php echo esc_url(home_url('/')); ?>" method="get">
			<input type="text" value="<?php echo get_search_query(); ?>" placeholder="Search" name="s" />
			<input type="submit" value="<?php echo esc_attr_x('Search', 'submit button'); ?>" />
		</form>

	</div>

</header>