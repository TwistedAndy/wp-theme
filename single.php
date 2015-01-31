<?php get_header(); ?>

<?php tw_set_views(get_the_ID()); ?>

<?php if (!have_posts()) { ?>

	<?php get_template_part('content', 'none'); ?>

<?php } else { ?>

	<?php while (have_posts()) { the_post(); ?>

	<div class="content">
		
		<h1><?php echo tw_wp_title(); ?></h1>
	
		<?php echo tw_thumb($post, 'post', '', '', array('class' => 'alignleft')); ?>
	
		<?php the_content(); ?>
	
	</div>
	
	<?php echo tw_navigation(array('type' => 'page')); ?>

	<?php if (comments_open() || get_comments_number()) comments_template(); ?>
	
	<?php } ?>
	
<?php } ?>

<?php get_footer(); ?>