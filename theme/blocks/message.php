<?php

if (!empty($block['image'])) {
	$block['contents']['before'] = tw_thumb($block['image'], 'full', '<div class="image">', '</div>');
}

if (!empty($block['password'])) {

	$block['contents']['after'] = '<form action="' . esc_url(site_url('wp-login.php?action=postpass', 'login_post')) . '" class="password post-password-form" method="post"><input name="post_password" aria-label="' . __('Password') . '" placeholder="' . __('Password') . '" type="password" size="20" /><button type="submit" class="button" name="Submit">' . esc_attr_x('Enter', 'post password form') . '</button></form>';

}

?>
<section <?php echo tw_block_attributes('message_box', $block); ?>>

	<div class="fixed">

		<?php echo tw_block_contents($block); ?>

	</div>

</section>