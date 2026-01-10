<?php
/**
 * Process and render the ACF Flexible Content blocks
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
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
function tw_block_render($blocks): string
{
	if (empty($blocks)) {
		return '';
	}

	if (isset($blocks['acf_fc_layout'])) {
		$blocks = [$blocks];
	}

	$is_front = !is_admin();

	$block_id = (int) tw_app_get('block_id', 'default', 0);

	ob_start();

	foreach ($blocks as $block) {

		if (!is_array($block) or empty($block['acf_fc_layout'])) {
			continue;
		}

		if (!empty($block['settings']) and !empty($block['settings']['options'])) {
			$options = $block['settings']['options'];
		} else {
			$options = [];
		}

		if (in_array('hidden', $options) and $is_front) {
			continue;
		}

		$filename = TW_ROOT . 'blocks/' . $block['acf_fc_layout'] . '.php';

		if (is_readable($filename)) {
			tw_app_set('block_id', $block_id, 'default');
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
function tw_block_attributes($class, array $block): string
{
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

	if (!empty($settings['options']) and is_array($settings['options'])) {

		$options = $settings['options'];

		if (in_array('border', $options)) {
			$classes[] = 'box_border';
		}

	}

	if (!empty($settings['top']) and $settings['top'] != 'default') {
		if ($settings['top'] == 'normal') {
			$classes[] = 'box_top';
		} elseif ($settings['top'] == 'half') {
			$classes[] = 'box_top_half';
		} elseif ($settings['top'] == 'none') {
			$classes[] = 'box_no_top';
		}
	}

	if (!empty($settings['bottom']) and $settings['bottom'] != 'default') {
		if ($settings['bottom'] == 'normal') {
			$classes[] = 'box_bottom';
		} elseif ($settings['bottom'] == 'half') {
			$classes[] = 'box_bottom_half';
		} elseif ($settings['bottom'] == 'none') {
			$classes[] = 'box_no_bottom';
		}
	}

	if (!empty($settings['background']) and $settings['background'] != 'default') {
		$classes[] = 'box_' . $settings['background'];
	}

	$result = ' class="' . implode(' ', $classes) . '"';

	if (isset($_GET['preview']) and empty($_GET['preview'])) {
		$result .= ' id="block_' . tw_app_get('block_id', 'default', 0) . '"';
		$result .= ' data-layout="' . esc_attr($block['acf_fc_layout']) . '"';
	} elseif (!empty($settings['block_id'])) {
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
function tw_block_contents(array $block, string $wrapper = 'contents'): string
{
	if (!empty($block['contents']) and is_array($block['contents'])) {
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
function tw_block_buttons(array $buttons, string $wrapper = 'buttons', string $size = '')
{
	if (!empty($buttons)) {
		$result = tw_app_template('buttons', ['buttons' => $buttons, 'wrapper' => $wrapper, 'size' => $size]);
	} else {
		$result = '';
	}

	return $result;
}