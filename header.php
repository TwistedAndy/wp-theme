<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="ru" lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width" />
	<?php if (!function_exists('_wp_render_title_tag')) { ?>
	<title><?php echo tw_wp_title(true); ?></title>
	<?php } ?>
	<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/style.css" />
	<?php wp_head(); ?>
</head>
<body>

	<?php wp_nav_menu(array('theme_location' => 'main', 'container' => '', 'container_class' => '', 'menu_class' => '', 'menu_id' => '')); ?>
	
	<?php if (!is_front_page()) { ?>
	<div class="breadcrumbs">
		<?php echo tw_breadcrumbs(''); ?>
	</div>
	<?php } ?>
	
	<form action="<?php echo get_site_url(); ?>/index.php" method="get" id="search">
		<input type="text" value="<?php echo get_search_query(); ?>" placeholder="Поиск по сайту" name="s" />
		<input type="submit" value="" />
	</form>
	
	<?php get_sidebar(); ?>
	
	<?php echo get_site_url(); ?>