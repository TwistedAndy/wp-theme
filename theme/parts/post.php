<?php

if (empty($item) or !($item instanceof WP_Post)) {
	return;
}

$link = get_permalink($item);

?>
<div class="card">

	<?php echo tw_thumb($item, 'thumbnail', '', '', ['link' => $link, 'link_class' => 'image']); ?>

	<div class="date"><?php echo tw_date($item, 'F j, Y'); ?></div>

	<a href="<?php echo $link; ?>" class="title"><?php echo tw_title($item); ?></a>

	<?php if ($text = tw_text($item, 160)) { ?>
		<div class="text"><?php echo $text; ?></div>
	<?php } ?>

	<a href="<?php echo $link; ?>" class="button outline">Continue Reading</a>

</div>