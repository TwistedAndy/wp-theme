<?php

$questions = [];

if (!empty($block['contents']) and !empty($block['contents']['buttons'])) {
	$buttons = $block['contents']['buttons'];
	unset($block['contents']['buttons']);
} else {
	$buttons = [];
}

$contents = tw_block_contents($block);
$buttons = tw_block_buttons($buttons);

?>
<section <?php echo tw_block_attributes('faq_box', $block); ?>>

	<div class="fixed">

		<?php echo $contents; ?>

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

		<?php echo $buttons; ?>

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