<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xml:lang="ru" lang="ru" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width" />
	<?php if (!function_exists('_wp_render_title_tag')) { ?>
	<title><?php wp_title('|', true, 'right'); ?></title>
	<?php } ?>
	<link rel="stylesheet" type="text/css" href="<?php bloginfo( 'template_url' ); ?>/style.css" />
	<?php wp_head(); ?>
</head>
<body>

	<?php wp_nav_menu(array('items_wrap' => '<ul>%3$s</ul>', 'theme_location' => 'main' )); ?>
	
	<?php get_sidebar(); ?>
	
	<?php if (!is_front_page()) { ?>
	<div class="breadcrumbs">
		<?php echo tw_breadcrumbs(''); ?>
	</div>
	<?php } ?>
	
	<?php echo get_site_url(); ?>