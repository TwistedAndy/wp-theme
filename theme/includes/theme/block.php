<?php
/**
 * Process and render the ACF Flexible Content blocks
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/**
 * Output the blocks from the ACF Flexible content field.
 * Block template files should be located in the "blocks" folder in the theme root directory.
 * Template file names should correspond to the ACF Flexible content layouts names.
 *
 * @param array $blocks array with blocks or a single block
 *
 * @return string
 */
function tw_block_render($blocks) {

	if (!is_array($blocks) or empty($blocks)) {
		return '';
	}

	if (isset($blocks['acf_fc_layout'])) {
		$blocks = [$blocks];
	}

	$block_id = 0;

	ob_start();

	foreach ($blocks as $block) {

		if (!is_array($block) or empty($block['acf_fc_layout'])) {
			continue;
		}

		$options = [];

		if (!empty($block['settings']) and !empty($block['settings']['options'])) {
			$options = $block['settings']['options'];
		}

		if (in_array('hidden', $options)) {
			continue;
		}

		$filename = TW_ROOT . 'blocks/' . $block['acf_fc_layout'] . '.php';

		if (is_readable($filename)) {

			include $filename;

			$block_id++;

		}

	}

	return ob_get_clean();

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

	foreach ($classes as $class) {
		if (strpos($class, '_box') > 0) {
			tw_asset_enqueue($class);
			break;
		}
	}

	$settings = [];

	if (!empty($block['settings'])) {
		$settings = $block['settings'];
	}

	if (!empty($settings['options']) and is_array($settings['options'])) {

		$options = $settings['options'];

		if (in_array('border', $options)) {
			$classes[] = 'box_border';
		}

		if (in_array('top', $options)) {
			$classes[] = 'box_no_top';
		}

		if (in_array('bottom', $options)) {
			$classes[] = 'box_no_bottom';
		}

	}

	if (!empty($settings['background']) and $settings['background'] != 'default') {
		$classes[] = 'box_' . $settings['background'];
	}

	$result = ' class="' . implode(' ', $classes) . '"';

	if (!empty($settings['block_id'])) {
		$result .= ' id="' . $settings['block_id'] . '"';
	}

	return $result;

}


/**
 * Render the block contents
 *
 * @param array  $block
 * @param string $wrapper
 *
 * @return string
 */
function tw_block_contents($block, $wrapper = 'contents') {

	if (is_array($block) and !empty($block['contents']) and is_array($block['contents'])) {
		$result = tw_app_template('contents', ['block' => $block['contents'], 'wrapper' => $wrapper]);
	} else {
		$result = '';
	}

	return $result;

}


/**
 * Render the buttons
 *
 * @param array  $buttons
 * @param string $wrapper
 * @param string $size
 *
 * @return string
 */
function tw_block_buttons($buttons, $wrapper = 'buttons', $size = '') {

	$result = '';

	if (is_array($buttons) and !empty($buttons)) {
		$result = tw_app_template('buttons', ['buttons' => $buttons, 'wrapper' => $wrapper, 'size' => $size]);
	}

	return $result;

}