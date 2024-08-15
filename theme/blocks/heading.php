<?php

if (!empty($block['background_1'])) {
	$image_1 = $block['background_1'];
} else {
	$image_1 = 0;
}

if (!empty($block['background_2'])) {
	$image_2 = $block['background_2'];
} else {
	$image_2 = 0;
}

if ($image_1 and empty($image_2)) {
	$image_2 = $image_1;
} elseif ($image_2 and empty($image_1)) {
	$image_1 = $image_2;
}

$class = ['heading_box'];

if (!empty($block['options']) and is_array($block['options'])) {
	$options = $block['options'];
} else {
	$options = [];
}

if (in_array('compact', $options)) {
	$class[] = 'is_compact';
}

$contents = tw_block_contents($block);

if (!empty($block['contents']) and !empty($block['contents']['title'])) {
	$title = $block['contents']['title'];
} else {
	$title = '';
}

?>
<section <?php echo tw_block_attributes($class, $block); ?>>

	<?php if ($contents) { ?>
		<div class="fixed">
			<?php echo $contents; ?>
		</div>
	<?php } ?>

	<?php echo tw_image($image_1, 'full', '<div class="background desktop">', '</div>', ['alt' => $title, 'loading' => 'lazy']); ?>
	<?php echo tw_image($image_2, 'full', '<div class="background mobile">', '</div>', ['alt' => $title, 'loading' => false]); ?>

</section>