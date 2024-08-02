<!DOCTYPE html>
<html <?php echo get_language_attributes('html'); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php do_action('wp_body_open'); ?>

<div id="site">

	<main>

		<a class="skip-link" href="#contents">Skip to Content</a>

		<?php echo tw_app_template('header'); ?>