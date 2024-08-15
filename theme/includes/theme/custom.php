<?php
/**
 * Custom PHP code
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/**
 * Add the custom code
 */
add_action('wp_head', function() {
	echo get_option('options_code_head', '');
});

add_action('wp_body_open', function() {
	echo get_option('options_code_body_1', '');
});

add_action('wp_footer', function() {
	echo get_option('options_code_body_2', '');
});


/**
 * Disable Gravity Forms styles
 */
add_filter('gform_disable_css', '__return_true');