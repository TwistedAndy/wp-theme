<?php

if (empty($item) or !($item instanceof WP_Post)) {
	return;
}

$link = get_permalink($item);

?>
<div class="item">

	<?php echo tw_image($item, 'thumbnail', '', '', [
		'link' => $link,
		'link_class' => 'image',
		'sizes' => ['ts' => 50, 'ds' => 33.333, 'dt' => 3]
	]); ?>

	<div class="date"><?php echo tw_content_date($item, 'F j, Y'); ?></div>

	<a href="<?php echo $link; ?>" class="title"><?php echo tw_content_title($item); ?></a>

	<?php if ($text = tw_content_text($item, 160)) { ?>
		<div class="text"><?php echo $text; ?></div>
	<?php } ?>

	<a href="<?php echo $link; ?>" class="link">Read More</a>

</div>