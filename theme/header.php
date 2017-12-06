<!DOCTYPE html>
<html <?php echo get_language_attributes('html'); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<?php wp_head(); ?>
</head>
<body>

	<?php echo get_site_url(); ?>

	<?php wp_nav_menu(array('theme_location' => 'main', 'container' => '', 'container_class' => '', 'menu_class' => '', 'menu_id' => '')); ?>

	<?php echo tw_breadcrumbs('','<div class="breadcrumbs">', '</div>'); ?>

	<form action="<?php echo get_site_url(); ?>/index.php" method="get" id="search">
		<input type="text" value="<?php echo get_search_query(); ?>" placeholder="Поиск по сайту" name="s" />
		<input type="submit" value="" />
	</form>