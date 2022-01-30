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

		<?php if ($items = get_field('socials', 'options')) { ?>
			<div class="socials">
				<?php foreach ($items as $item) { ?>
					<a href="<?php echo esc_url($item['link']); ?>" class="social social_<?php echo $item['icon']; ?>" target="_blank" aria-label="<?php echo ucfirst($item['icon']); ?>"></a>
				<?php } ?>
			</div>
		<?php } ?>

	</div>

</footer>