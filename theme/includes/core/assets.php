<?php
/**
 * Asset management library. It allows to register and enqueue
 * the styles, scripts and the localization strings
 *
 * @author  Toniievych Andrii <toniyevych@gmail.com>
 * @package wp-theme
 * @version 2.0
 */


/**
 * Register all custom assets with their configuration
 */

add_action('init', 'tw_register_assets');

function tw_register_assets() {

	$assets = tw_get_setting('assets');

	if (empty($assets)) {
		$assets = array();
	}

	$base_directory = TW_ROOT . '/assets/plugins/';

	$directories = scandir($base_directory);

	if (is_array($directories)) {

		$directories = array_diff($directories, array('..', '.'));

		foreach ($directories as $directory) {

			$filename = $base_directory . $directory . '/index.php';

			if (is_file($filename)) {

				$array = include($filename);

				if (is_array($array)) {

					if (!empty($array['script'])) {

						if (!is_array($array['script'])) {
							$array['script'] = array($array['script']);
						}

						foreach ($array['script'] as $key => $value) {
							if (strpos($value, 'http') !== 0) {
								$array['script'][$key] = 'plugins/' . $directory . '/' . $value;
							}
						}

					}

					if (!empty($array['style'])) {

						if (!is_array($array['style'])) {
							$array['style'] = array($array['style']);
						}

						foreach ($array['style'] as $key => $value) {
							if (strpos($value, 'http') !== 0) {
								$array['style'][$key] = 'plugins/' . $directory . '/' . $value;
							}
						}

					}

					if (isset($assets[$directory])) {

						if (is_array($assets[$directory])) {
							$assets[$directory] = wp_parse_args($assets[$directory], $array);
						} elseif (is_bool($assets[$directory]) and $assets[$directory]) {
							$assets[$directory] = $array;
							$assets[$directory]['display'] = true;
						} else {
							$assets[$directory] = $array;
						}

					} else {

						$assets[$directory] = $array;

					}

				}

			}

		}

	}

	foreach ($assets as $name => $asset) {

		tw_register_asset($name, $asset);

		tw_set_setting('assets', $name, $asset);

	}

}


/**
 * Register a single asset
 *
 * @param string $name  Name of the asset. It should be unique.
 * @param array  $asset The array with the asset configuration
 *
 * @return bool
 */

function tw_register_asset($name, $asset) {

	if (is_array($asset)) {

		$base_url = get_template_directory_uri() . '/assets/';

		$defaults = array(
			'deps' => array(),
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
						$script = $base_url . $script;
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
						$style = $base_url . $style;
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
 * Enqueue all registered assets
 */

add_action('wp_enqueue_scripts', 'tw_enqueue_assets');

function tw_enqueue_assets() {

	$assets = tw_get_setting('assets');

	if ($assets) {

		foreach ($assets as $name => $asset) {

			if ((is_array($asset) and !empty($asset['display'])) or $asset === true) {
				tw_enqueue_asset($name);
			}

		}

	}

}


/**
 * Enqueue a single asset
 *
 * @param string $name Name of the asset.
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