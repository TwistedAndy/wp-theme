<?php
/**
 * Asset management library. It allows to register and enqueue
 * the styles, scripts and the localization strings
 *
 * @author  Toniyevych Andriy <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Register a set of default and custom assets
 */

add_action('init', 'tw_register_assets');

function tw_register_assets() {

	$assets = tw_get_setting('assets');

	if (!empty($assets) and is_array($assets)) {

		foreach ($assets as $name => $asset) {

			$filename = TW_ROOT . '/assets/plugins/' . $name . '/index.php';

			$defaults = false;

			if (is_file($filename)) {

				$array = include($filename);

				if (is_array($array)) {

					if (!empty($array['script']) and is_string($array['script'])) {
						$array['script'] = array($array['script']);
					}

					foreach ($array['script'] as $key => $value) {
						$array['script'][$key] = 'plugins/' . $name . '/' . $array['script'][$key];
					}

					if (!empty($array['style']) and is_string($array['style'])) {
						$array['style'] = array($array['style']);
					}

					foreach ($array['style'] as $key => $value) {
						$array['style'][$key] = 'plugins/' . $name . '/' . $array['style'][$key];
					}

					$defaults = $array;

				}

			}

			if (is_array($asset) and $defaults) {
				$asset = wp_parse_args($asset, $defaults);
			} elseif (is_bool($asset) and $asset) {
				$asset = $defaults;
				$asset['display'] = true;
			} elseif ($defaults) {
				$asset = $defaults;
			}

			tw_register_asset($name, $asset);
			tw_set_setting('registred_assets', $name, $asset);

		}

	}

}


/**
 * Register a single asset
 *
 * @param string $name Name of the asset. It should be unique.
 * @param array $asset The array with the asset configuration
 *
 * @return bool
 */

function tw_register_asset($name, $asset) {

	if (is_array($asset)) {

		$dir = get_template_directory_uri() . '/assets/';

		$defaults = array(
			'deps' => array('jquery'),
			'style' => '',
			'script' => '',
			'footer' => true,
			'display' => false,
			'localize' => array()
		);

		$asset = wp_parse_args($asset, $defaults);

		if (!empty($asset['script'])) {

			if (is_string($asset['script'])) {
				$asset['script'] = array($asset['script']);
			}

			if (is_array($asset['script'])) {

				$i = count($asset['script']) - 1;

				$current_key = false;

				foreach ($asset['script'] as $script) {

					$previous_key = $current_key;

					if ($i == 0) {
						$current_key = $name;
					} else {
						$current_key = $name . '-' . $i;
					}

					if ($previous_key) {
						$asset['deps'][] = $previous_key;
					}

					if (strpos($script, 'http') !== 0) {
						$script = $dir . $script;
					}

					wp_register_script($current_key, $script, $asset['deps'], null, $asset['footer']);

					$i--;

				}

			}

			if ($asset['localize']) {
				wp_localize_script($name, $name, $asset['localize']);
			}

		}

		if (!empty($asset['style'])) {

			if (is_string($asset['style'])) {
				$asset['style'] = array($asset['style']);
			}

			if (is_array($asset['style'])) {

				$i = count($asset['style']) - 1;

				$current_key = false;

				$deps = array();

				foreach ($asset['style'] as $style) {

					$previous_key = $current_key;

					if ($i == 0) {
						$current_key = $name;
					} else {
						$current_key = $name . '-' . $i;
					}

					if ($previous_key) {
						$deps[] = $previous_key;
					}

					if (strpos($style, 'http') !== 0) {
						$style = $dir . $style;
					}

					wp_register_style($current_key, $style, $deps, null);

					$i--;

				}

			}

		}

	}

	return false;

}


/**
 * Enqueue all previously registered assets
 */

add_action('wp_enqueue_scripts', 'tw_enqueue_assets');

function tw_enqueue_assets() {

	$registred_assets = tw_get_setting('registred_assets');

	if ($registred_assets) {

		foreach ($registred_assets as $name => $asset) {

			if ((is_array($asset) and !empty($asset['display'])) or $asset === true) {
				tw_enqueue_asset($name);
			}

			if (!empty($asset['deps'])) {
				foreach ($asset['deps'] as $dep) {
					tw_enqueue_asset($dep);
				}
			}

		}

	}

}


/**
 * @param string $name Name of the previously registered asset.
 *
 * @return bool
 */

function tw_enqueue_asset($name) {

	if (wp_script_is($name, 'registered')) {
		wp_enqueue_script($name);
	}

	if (wp_style_is($name, 'registered')) {
		wp_enqueue_style($name);
	}

	return false;

}







