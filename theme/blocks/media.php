<?php

if (!empty($block['options'])) {
	$options = $block['options'];
} else {
	$options = [];
}

$classes = ['media_box'];

$size = 'medium';

if (in_array('reverse', $options)) {
	$classes[] = 'is_reversed';
}

if (in_array('full', $options)) {
	$classes[] = 'is_full';
	$size = 'full';
}

if (in_array('card', $options)) {
	$classes[] = 'is_card';
}

?>
<section <?php echo tw_block_attributes($classes, $block); ?>>

	<div class="fixed">
		<?php

		if (!empty($block['image'])) {

			if (!empty($block['video'])) {

				tw_asset_enqueue('bigger');

				echo '<div class="media media_video">';

				echo tw_image($block['image'], $size, '', '', [
					'link' => $block['video'],
					'link_class' => 'image',
					'after' => '<span class="play"></span>'
				]);

			} else {

				echo '<div class="media">';

				echo tw_image($block['image'], $size, '<div class="image">', '</div>');

			}

			echo '</div>';

		}

		$contents = tw_block_contents($block);

		if ($contents) {
			echo '<div class="wrapper">' . $contents . '</div>';
		}

		?>
	</div>

</section>