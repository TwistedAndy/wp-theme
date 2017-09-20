<?php
/**
 * Post views library
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Get the number of post views
 *
 * @param $post_id
 *
 * @return int
 */

function tw_get_views($post_id) {

	$count = intval(get_post_meta($post_id, 'post_views_count', true));

	return $count;

}


/**
 * Increase the number of post views
 *
 * @param $post_id
 */

function tw_set_views($post_id) {

	$count = tw_get_views($post_id);

	$count++;

	update_post_meta($post_id, 'post_views_count', $count);

}