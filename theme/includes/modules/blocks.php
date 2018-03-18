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
 * @param array|string $blocks   Field name or the ACF Flexible content field array
 * @param bool|array   $defaults Array with the default blocks
 */

function tw_get_blocks($blocks = 'blocks', $defaults = false) {

	if (is_string($blocks)) {
		$blocks = get_field($blocks);
	}

	if (is_array($blocks)) {

		$block_id = 1;

		foreach ($blocks as $block) {

			if (!empty($block['acf_fc_layout'])) {

				$block = tw_get_block($block, $defaults);

				$filename = TW_ROOT . '/blocks/' . $block['acf_fc_layout'] . '.php';

				if (is_file($filename)) {

					include $filename;

					$block_id++;

				}

			}

		}

	}

}


/**
 * Load the default block settings if necessary
 *
 * @param array $block    ACF Flexible content field array
 * @param array $defaults Default field array
 *
 * @return array
 */

function tw_get_block($block, $defaults) {

	if (!empty($block['default']) and is_array($defaults) and !empty($defaults)) {

		foreach ($defaults as $default) {

			if (!empty($default['acf_fc_layout']) and $block['acf_fc_layout'] == $default['acf_fc_layout']) {
				$block = $default;
				break;
			}

		}

	}

	return $block;

}