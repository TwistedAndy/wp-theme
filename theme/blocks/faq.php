<section <?php echo tw_block_attributes('faq_box', $block); ?>>

	<?php $questions = []; ?>

	<div class="fixed">

		<?php echo tw_block_contents($block, true); ?>

		<?php if (!empty($block['items'])) { ?>
			<div class="items">
				<?php foreach ($block['items'] as $index => $item) { ?>
					<div class="item">
						<div class="heading">
							<h3><?php echo $item['title']; ?></h3>
							<button class="toggle" type="button" aria-label="<?php esc_attr_e('Toggle Question', 'twee'); ?>"></button>
						</div>
						<div class="content">
							<?php echo $item['text']; ?>
						</div>
					</div>
					<?php
					$questions[] = [
						'@type' => 'Question',
						'name' => $item['title'],
						'acceptedAnswer' => [
							'@type' => 'Answer',
							'text' => $item['text']
						]
					];
					?>
				<?php } ?>
			</div>
		<?php } ?>

	</div>

</section>
<?php
if ($questions) {
	tw_microdata_add([
		'@context' => 'https://schema.org',
		'@type' => 'FAQPage',
		'mainEntity' => $questions
	]);
}
?>