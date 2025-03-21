<?php

if (!empty($block['image'])) {
	$block['contents']['before'] = tw_image($block['image'], 'full', '<div class="image">', '</div>');
}

if (!empty($block['password'])) {

	$block['contents']['after'] = '<form action="' . esc_url(site_url('wp-login.php?action=postpass', 'login_post')) . '" class="password post-password-form" method="post"><input name="post_password" aria-label="' . __('Password') . '" placeholder="' . __('Password') . '" type="password" size="20" /><button type="submit" class="button" name="Submit">' . esc_attr_x('Enter', 'post password form') . '</button></form>';

} elseif (!empty($block['search'])) {

	$block['contents']['middle'] = '<form action="' . esc_url(home_url()) . '" class="password post-password-form" method="post"><input name="s" aria-label="' . __('Search...', 'twee') . '" placeholder="' . __('Search...', 'twee') . '" value="' . get_search_query() . '" type="search" /><button type="submit" class="button" name="Submit">' . esc_html__('Search', 'twee') . '</button></form>';

}

if (!empty($block['options'])) {
	$options = $block['options'];
} else {
	$options = [];
}

$class = ['message_box'];

?>
<section <?php echo tw_block_attributes($class, $block); ?>>

	<div class="fixed">

		<?php echo tw_block_contents($block); ?>

	</div>

</section>