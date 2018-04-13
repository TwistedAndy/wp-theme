<?php
/**
 * Process and output the ACF Flexible content field blocks
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 *
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

	if (is_string($blocks)) {
		$blocks = get_field('blocks', $post_id);
	}

	if ($blocks and is_array($blocks)) {
		
		foreach ($blocks as $block) {

			if (!empty($block['acf_fc_layout'])) {

				tw_get_block($block);

			}

		}

	}

	return false;

}


/**
 * Include the ACF flexible content block
 *
 * @param array|string $block Block array or the layout name
 *
 * @return bool
 */

function tw_get_block($block) {

	static $block_id;

	$block = tw_get_block_defaults($block);

	if (is_array($block) and !empty($block['acf_fc_layout'])) {

		$filename = TW_ROOT . '/blocks/' . $block['acf_fc_layout'] . '.php';

		if (is_file($filename)) {

			include $filename;

			$block_id++;

		}

	}

	return false;

}


/**
 * Get the default block settings if it's necessary
 *
 * @param array|string $block Block array or the layout name
 *
 * @return array
 */

function tw_get_block_defaults($block) {

	if (tw_get_setting('modules', 'blocks', 'load_default')) {

		$layout = false;

		if (is_array($block) and !empty($block['default']) and !empty($block['acf_fc_layout'])) {

			$layout = $block['acf_fc_layout'];

		} elseif (is_string($block)) {

			$layout = $block;

		}

		if ($layout) {

			$blocks = tw_get_setting('blocks', 'defaults');

			if (empty($blocks)) {

				$blocks = get_field(tw_get_setting('modules', 'blocks', 'option_field'), 'option');

				if ($blocks and is_array($blocks)) {

					$blocks = array_shift($blocks);

				}

				tw_set_setting('blocks', 'defaults', $blocks);

			}

			if (is_array($blocks)) {

				foreach ($blocks as $default_block) {

					if (!empty($default_block['acf_fc_layout']) and $default_block['acf_fc_layout'] == $layout) {
						$block = $default_block;
						break;
					}

				}

			}

		}

	}

	return $block;

}