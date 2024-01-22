<?php
/**
 * Legacy Functions
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/**
 * Get a title of a post or term
 *
 * @param WP_Post|WP_Term $object Post object with a title
 * @param int             $length Maximum length of the title
 *
 * @return string
 */
function tw_title($object, $length = 0) {
	return tw_content_title($object, $length);
}


/**
 * Get the short description for a given post or text
 *
 * @param bool|string|WP_Post|WP_Term|WP_User $object       Post or text to find and strip the text
 * @param int                                 $length       Required length of the text
 * @param bool|string                         $allowed_tags List of tags separated by "|"
 * @param string                              $find         Symbol to find for proper strip
 * @param bool                                $force_cut    Strip the post excerpt
 *
 * @return bool|string
 */
function tw_text($object, $length = 250, $allowed_tags = false, $find = ' ', $force_cut = true) {
	return tw_content_text($object, $length, $allowed_tags, $find, $force_cut);
}


/**
 * Get the formatted date and time for a post
 *
 * @param WP_Post $post   Post object of false to use the current one
 * @param string  $format Date and time format
 *
 * @return string
 */
function tw_date($post, $format = '') {
	return tw_content_date($post, $format);
}


/**
 * Get the thumbnail with given size
 *
 * @param int|array|WP_Post $image      A post object, ACF image array, or an attachment ID
 * @param string|array      $size       Size of the image
 * @param string            $before     Code before thumbnail
 * @param string            $after      Code after thumbnail
 * @param array             $attributes Array with attributes
 *
 * @return string
 */
function tw_thumb($image, $size = 'full', $before = '', $after = '', $attributes = []) {
	return tw_image($image, $size, $before, $after, $attributes);
}


/**
 * Render a template with specified data
 *
 * @param string                  $name   Template part name
 * @param array|\WP_Post|\WP_Term $item   Array with data
 * @param string                  $folder Folder with template part
 *
 * @return string
 */
function tw_template_part($name, $item = [], $folder = 'parts') {
	return tw_app_template($name, $item, $folder);
}