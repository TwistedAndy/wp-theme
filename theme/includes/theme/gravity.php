<?php
/**
 * Gravity Forms Customizations
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */


/**
 * Disable the default Gravity Forms Styles
 */
add_filter('gform_disable_css', '__return_true');


/**
 * Remove additional image sizes
 */
add_filter('gform_image_sizes', '__return_empty_array');


/**
 * Enqueue Gravity Forms Styles
 */
add_filter('gform_default_styles', function($result) {
	tw_asset_enqueue('gravity');
	return $result;
});