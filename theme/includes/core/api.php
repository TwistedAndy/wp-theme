<?php

use \Twee\App;

/**
 * Get the title for the current page
 *
 * @param string $before          Code to prepend to the title
 * @param string $after           Code to append to the title
 * @param bool   $add_page_number Add a page number to the title
 *
 * @return string
 */
function tw_wp_title($before = '', $after = '', $add_page_number = false) {
	return App::getContent()->heading($before, $after, $add_page_number);
}


/**
 * Get a title of a post or term
 *
 * @param WP_Post|WP_Term $object Post object with a title
 * @param int             $length Maximum length of the title
 *
 * @return string
 */
function tw_title($object, $length = 0) {
	return App::getContent()->title($object, $length);
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
	return App::getContent()->text($object, $length, $allowed_tags, $find, $force_cut);
}


/**
 * Strip the text to a given length
 *
 * @param string      $text         Text to strip
 * @param int         $length       Required length of the text
 * @param bool|string $allowed_tags List of tags separated by "|"
 * @param string      $find         Symbol to find for proper strip
 * @param string      $dots         Text after the stripped text
 *
 * @return string
 */
function tw_strip_text($text, $length = 200, $allowed_tags = false, $find = ' ', $dots = '...') {
	return App::getContent()->strip($text, $length, $allowed_tags, $find, $dots);
}


/**
 * Get the formatted phone number for a link href attribute
 *
 * @param string $string String with phone number
 *
 * @return string
 */
function tw_phone($string) {
	return App::getContent()->phone($string);
}


/**
 * Get the formatted date and time for a post
 *
 * @param bool|WP_Post $post   Post object of false to use the current one
 * @param string       $format Date and time format
 *
 * @return string
 */
function tw_date($post = false, $format = '') {
	return App::getContent()->date($post, $format);
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
	return App::getImage()->getThumb($image, $size, $before, $after, $attributes);
}


/**
 * Get the thumbnail url
 *
 * @param int|array|WP_Post $image WordPress Post object, ACF image array or an attachment ID
 * @param string|array      $size  Size of the thumbnail
 *
 * @return string
 */
function tw_thumb_link($image, $size = 'full') {
	return App::getImage()->getLink($image, $size);
}


/**
 * Get the thumbnail as a background image
 *
 * @param int|array|WP_Post $image A post object, ACF image or an attachment ID
 * @param string|array      $size  Size of the image
 * @param bool              $style Include the style attribute
 *
 * @return string
 */
function tw_thumb_background($image, $size = 'full', $style = true) {
	return App::getImage()->getBackground($image, $size, $style);
}


/**
 * Enqueue a single asset
 *
 * @param string $name Name of the asset
 */
function tw_asset_enqueue($name) {
	App::getAssets()->enqueue($name);
}


/**
 * Get names or links to all post terms
 *
 * @param bool|int $post_id   Post ID or false for the current post
 * @param string   $taxonomy  Term taxonomy
 * @param bool     $with_link Wrap term a link
 * @param string   $class     Link class
 *
 * @return array
 */
function tw_term_links($post_id = false, $taxonomy = 'category', $class = 'category', $with_link = true) {
	return App::getTerms()->postTermLinks($post_id, $taxonomy, $class, $with_link);
}


/**
 * Get the post term IDs as a comma-separated values or as an array
 *
 * @param bool|int    $post_id         Post ID or false for the current post
 * @param bool|string $taxonomy        Post taxonomy
 * @param bool        $include_parents Include parent terms
 *
 * @return array|string
 */
function tw_term_list($post_id = false, $taxonomy = false, $include_parents = false) {
	return App::getTerms()->postTerms($post_id, $taxonomy, $include_parents);
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
	return App::getApp()->renderTemplate($name, $item, $folder);
}