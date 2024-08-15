<?php tw_asset_enqueue('footer_box'); ?>

<footer class="footer_box">

	<div class="fixed">

		<?php if (has_nav_menu('bottom')) { ?>
			<?php wp_nav_menu([
				'theme_location' => 'bottom',
				'container' => '',
				'container_class' => '',
				'menu_class' => 'menu',
				'menu_id' => ''
			]); ?>
		<?php } ?>

		<?php if ($items = get_option('options_socials', false) and is_array($items)) { ?>
			<div class="socials">
				<?php foreach ($items as $item) { ?>
					<a href="<?php echo esc_url($item['link']); ?>" class="social social_<?php echo $item['icon']; ?>" target="_blank" aria-label="<?php echo ucfirst($item['icon']); ?>"></a>
				<?php } ?>
			</div>
		<?php } ?>

		<?php if (is_active_sidebar('footer')) { ?>
			<?php dynamic_sidebar('footer'); ?>
		<?php } ?>

	</div>

</footer>