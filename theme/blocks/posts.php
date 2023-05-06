<?php

$type = 'post';
$template = 'post';
$wrapper = 'posts_box';
$taxonomies = ['category', 'post_tag'];

if (!empty($block['type'])) {
	$type = $block['type'];
}

if (!empty($block['template'])) {
	$template = $block['template'];
}

if ($type == 'product') {
	$taxonomies = ['product_cat'];
}

$options = [];

if (!empty($block['options']) and is_array($block['options'])) {
	$options = $block['options'];
}

if (in_array('current', $options)) {

	global $wp_query;

	$query = $wp_query;

	$items = $query->posts;

} else {

	$args = [
		'post_type' => $type,
		'post_status' => 'publish',
		'posts_per_page' => 6,
		'orderby' => 'date',
		'order' => 'DESC',
		'offset' => 0
	];

	if (in_array('exclude', $options)) {

		$object = get_queried_object();

		if ($object instanceof WP_Post) {

			if (empty($block['exclude'])) {
				$block['exclude'] = [];
			}

			$block['exclude'][] = $object->ID;

		}

	}

	if (!empty($block['exclude'])) {
		$args['post__not_in'] = $block['exclude'];
	}

	if (!empty($block['number'])) {
		$args['posts_per_page'] = intval($block['number']);
	}

	if (!empty($block['offset'])) {
		$args['offset'] = intval($block['offset']);
	}

	$tax_query = [];

	if ($taxonomies) {
		foreach ($taxonomies as $taxonomy) {
			if (!empty($block[$taxonomy]) and is_array($block[$taxonomy])) {
				$tax_query[] = [
					'taxonomy' => $taxonomy,
					'field' => 'term_id',
					'terms' => $block[$taxonomy],
				];
			}
		}
	}

	if ($tax_query) {
		$tax_query['relation'] = 'AND';
		$args['tax_query'] = $tax_query;
	}

	$order = 'date';

	if (!empty($block['order'])) {
		$order = $block['order'];
	}

	if ($order == 'custom' and !empty($block['items'])) {

		$args['post__in'] = $block['items'];
		$args['orderby'] = 'post__in';
		$args['order'] = 'ASC';

	} elseif ($order == 'menu_order') {

		$args['orderby'] = 'menu_order title';
		$args['order'] = 'ASC';

	} else {

		$args['orderby'] = $order;

		if ($order == 'date') {
			$args['order'] = 'DESC';
		} else {
			$args['order'] = 'ASC';
		}

	}

	$query = new WP_Query($args);

	$items = $query->posts;

}

if (empty($items)) {
	return;
}

$classes = ['items'];

if (in_array('slider', $options)) {
	$classes[] = 'slider';
	tw_asset_enqueue('flickity');
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

		<div class="wrapper">

			<div class="<?php echo implode(' ', $classes); ?>">
				<?php foreach ($items as $item) { ?>
					<?php echo tw_app_template($template, ['item' => $item]); ?>
				<?php } ?>
			</div>

			<?php if (in_array('slider', $options)) { ?>
				<div class="dots">
					<?php foreach ($items as $index => $item) { ?>
						<button type="button" aria-label="Slide #<?php echo($index + 1); ?>"></button>
					<?php } ?>
				</div>
				<button class="arrow_prev" type="button" aria-label="Previous Slide"></button>
				<button class="arrow_next" type="button" aria-label="Next Slide"></button>
			<?php } ?>

		</div>

		<?php if (in_array('loader', $options)) { ?>
			<?php tw_loader_button('.' . $wrapper, $template, $query); ?>
		<?php } elseif (in_array('current', $options)) { ?>
			<?php echo tw_pagination(); ?>
		<?php } elseif ($buttons) { ?>
			<?php echo tw_block_buttons($buttons); ?>
		<?php } ?>

	</div>

</section>