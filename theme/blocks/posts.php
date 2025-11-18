<?php

$type = 'post';
$template = 'post';
$wrapper = 'posts_box';

if (!empty($block['type'])) {
	$type = $block['type'];
}

if (!empty($block['template'])) {
	$template = $block['template'];
}

$options = [];

if (!empty($block['options']) and is_array($block['options'])) {
	$options = $block['options'];
}

if (in_array('current', $options)) {

	global $wp_query;

	$query = $wp_query;

} else {

	if (in_array('exclude', $options)) {

		$object = get_queried_object();

		if ($object instanceof WP_Post) {

			if (empty($block['exclude'])) {
				$block['exclude'] = [];
			}

			$block['exclude'][] = $object->ID;

		}

	}

	$args = tw_post_query($type, $block);

	$query = new WP_Query($args);

}

if (empty($query->posts)) {
	return;
}

$items = $query->posts;

$classes = ['items'];

if (in_array('slider', $options)) {
	$classes[] = 'carousel';
	tw_asset_enqueue('embla');
}

$buttons = [];

if (!empty($block['contents']) and !empty($block['contents']['buttons'])) {
	$buttons = $block['contents']['buttons'];
	unset($block['contents']['buttons']);
}

?>

<section <?php echo tw_block_attributes($wrapper, $block); ?>>

	<div class="fixed">

		<?php echo tw_block_contents($block); ?>

		<div class="<?php echo implode(' ', $classes); ?>">
			<?php foreach ($items as $item) { ?>
				<?php echo tw_app_template($template, ['item' => $item]); ?>
			<?php } ?>
		</div>

		<?php if (in_array('current', $options)) { ?>
			<?php echo tw_pagination(); ?>
		<?php } elseif (in_array('loader', $options)) { ?>
			<?php tw_loader_button('.' . $wrapper, $template, $query); ?>
		<?php } elseif ($buttons) { ?>
			<?php echo tw_block_buttons($buttons); ?>
		<?php } ?>

	</div>

</section>