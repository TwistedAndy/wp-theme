<?php
/**
 * Process and output the ACF Flexible content field blocks
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */


/**
 * Output the blocks from the ACF Flexible content field.
 * Block template files should be located in the "blocks" folder in the theme root directory.
 * Template file names should correspond to the ACF Flexible content layouts names.
 *
 * @param array|string $blocks  Field name or the ACF Flexible content field array
 * @param bool         $post_id ACF field ID
 *
 * @return bool
 */
function tw_get_blocks($blocks = 'blocks', $post_id = false) {

	ob_start();

	if (is_string($blocks)) {
		$blocks = get_field($blocks, $post_id);
	}

	if ($blocks and is_array($blocks)) {

		foreach ($blocks as $block) {

			if (!empty($block['acf_fc_layout'])) {

				tw_get_block($block);

			}

		}

	}

	$result = ob_get_contents();

	ob_end_clean();

	return $result;

}


/**
 * Include the ACF flexible content block
 *
 * @param array $block Block array or the layout name
 *
 * @return bool
 */
function tw_get_block($block) {

	static $block_id;

	if (is_array($block) and !empty($block['acf_fc_layout']) and empty($block['hidden']) and (empty($block['settings']) or empty($block['settings']['hidden']))) {

		$filename = TW_ROOT . 'blocks/' . $block['acf_fc_layout'] . '.php';

		if (is_file($filename)) {

			include $filename;

			$block_id++;

		}

	}

	return false;

}


/**
 * Output the block attributes based on the settings array
 *
 * @param string|array $class The block default class
 * @param array        $block The block array
 *
 * @return string
 */
function tw_block_attributes($class, $block) {

	$classes = [];

	if (is_string($class)) {
		$classes = [$class];
	} elseif (is_array($class)) {
		$classes = $class;
	}

	$settings = [];

	if (!empty($block['settings'])) {
		$settings = $block['settings'];
	}

	if (!empty($settings['has_background'])) {
		$classes[] = 'has_background';
	}

	if (!empty($settings['has_border'])) {
		$classes[] = 'has_border';
	}

	$result = ' class="' . implode(' ', $classes) . '"';

	if (!empty($settings['block_id'])) {
		$result .= ' id="' . $settings['block_id'] . '"';
	}

	return $result;

}