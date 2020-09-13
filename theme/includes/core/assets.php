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
 * Register all custom assets
 */

add_action('init', 'tw_assets_register', 20);

function tw_assets_register() {

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

				$asset = include($filename);

				if (is_array($asset)) {

					if (isset($assets[$directory])) {

						$array = $assets[$directory];

						if (is_callable($array) or is_bool($array)) {
							$array = array(
								'display' => $array
							);
						}

						if (is_array($asset)) {
							$asset = wp_parse_args($array, $asset);
						}

					}

					$assets[$directory] = $asset;

				}

			}

		}

	}

	tw_set_setting(false, 'assets', $assets);

	foreach ($assets as $name => $asset) {

		if (is_array($directories) and in_array($name, $directories)) {
			$directory = 'plugins/' . $name . '/';
		} else {
			$directory = '';
		}

		tw_asset_register($name, $asset, $directory);

	}

}


/**
 * Enqueue all registered assets
 */

add_action('wp_enqueue_scripts', 'tw_assets_enqueue', 20);

function tw_assets_enqueue() {

	$assets = tw_get_setting('assets');

	if ($assets) {

		foreach ($assets as $name => $asset) {

			if (!empty($asset['display'])) {

				if (is_callable($asset['display'])) {
					$asset['display'] = call_user_func($asset['display']);
				}

				if ($asset['display']) {
					tw_asset_enqueue($name);
				}

			}

		}

	}

}


/**
 * Register a single asset
 *
 * @param string $name      Name of the asset. It should be unique.
 * @param array  $asset     The array with the asset configuration
 * @param string $directory Folder for styles and scripts. The base directory is {$theme_url}/assets/
 *
 * @return bool
 */

function tw_asset_register($name, $asset, $directory = '') {

	if (is_array($asset)) {

		$asset = tw_asset_normalize($asset, $directory);

		$asset = apply_filters('tw/asset/register/' . $name, $asset);

		tw_set_setting('assets', $name, $asset);

		if (empty($asset['prefix'])) {
			$asset_name = $name;
		} else {
			$asset_name = $asset['prefix'] . $name;
		}

		$deps = array();

		foreach (array('script', 'style') as $type) {

			if (!empty($asset[$type]) and is_array($asset[$type])) {

				$i = count($asset[$type]) - 1;

				$current_key = false;

				if (!empty($asset['deps'][$type]) and is_array($asset['deps'][$type])) {
					$deps[$type] = $asset['deps'][$type];
				} else {
					$deps[$type] = array();
				}

				foreach ($asset[$type] as $file) {

					$previous_key = $current_key;

					if ($i == 0) {
						$current_key = $asset_name;
					} else {
						$current_key = $asset_name . '-' . $i;
					}

					if ($previous_key) {
						$deps[$type][] = $previous_key;
					}

					if ($type == 'script') {
						wp_register_script($current_key, $file, $deps[$type], $asset['version'], $asset['footer']);
					} elseif ($type == 'style') {
						wp_register_style($current_key, $file, $deps[$type], $asset['version']);
					}

					$i--;

				}

			}

		}

	}

	return false;

}


/**
 * Enqueue a single asset
 *
 * @param string $name Name of the asset
 *
 * @return bool
 */

function tw_asset_enqueue($name) {
	
	$asset = tw_get_setting('assets', $name);

	$asset_name = $name;

	if (is_array($asset)) {

		$asset = apply_filters('tw/asset/enqueue/' . $name, $asset);

		if (!empty($asset['prefix'])) {
			$asset_name = $asset['prefix'] . $name;
		}

		if (!empty($asset['localize'])) {

			if (is_callable($asset['localize'])) {
				$asset['localize'] = call_user_func($asset['localize']);
			}

			if (is_array($asset['localize'])) {
				wp_localize_script($asset_name, $name, $asset['localize']);
			}

		}

	}
	
	if (wp_script_is($asset_name, 'registered')) {
		wp_enqueue_script($asset_name);
	}

	if (wp_style_is($asset_name, 'registered')) {
		wp_enqueue_style($asset_name);
	}

	return false;

}


/**
 * Normalize an asset configuration and check dependencies
 *
 * @param array  $asset     Array with asset configuration
 * @param string $directory Folder for styles and scripts. The base directory is {$theme_url}/assets/
 *
 * @return array
 */

function tw_asset_normalize($asset, $directory = '') {

	if (is_array($asset)) {

		$base_url = get_template_directory_uri() . '/assets/';

		$defaults = array(
			'deps' => array(
				'style' => array(),
				'script' => array()
			),
			'style' => '',
			'script' => '',
			'footer' => true,
			'prefix' => 'tw_',
			'version' => null,
			'display' => false,
			'localize' => array()
		);

		$asset = wp_parse_args($asset, $defaults);

		foreach (array('style', 'script') as $type) {

			if (!empty($asset[$type])) {

				if (is_string($asset[$type])) {
					$asset[$type] = array($asset[$type]);
				}

				foreach ($asset[$type] as $key => $value) {

					if (strpos($value, 'http') !== 0) {

						$asset[$type][$key] = $base_url . $directory . $value;

					}

				}

			}

		}

		if (!empty($asset['deps'])) {

			if (is_string($asset['deps'])) {
				$asset['deps'] = array($asset['deps']);
			}

			if (is_array($asset['deps'])) {

				$assets = tw_get_setting('assets');

				if (empty($asset['prefix'])) {
					$prefix = '';
				} else {
					$prefix = $asset['prefix'];
				}

				$deps = array();

				foreach (array('script', 'style') as $type) {

					if (isset($asset['deps'][$type]) and empty($asset['deps'][$type])) {
						continue;
					}

					$asset_deps = array();

					if (!empty($asset['deps'][$type]) and is_array($asset['deps'][$type])) {

						if (is_string($asset['deps'][$type])) {
							$asset['deps'][$type] = array($asset['deps'][$type]);
						}

						if (!empty($asset['deps'][$type][0]) and is_string($asset['deps'][$type][0])) {
							$asset_deps = $asset['deps'][$type];
						}

					} else {
						
						if ($type !== 'script' and !empty($asset['deps']['script']) and is_string($asset['deps']['script'][0])) {
							$asset_deps = array_merge($asset_deps, $asset['deps']['script']);
						}
						
						if ($type !== 'style' and !empty($asset['deps']['style']) and is_string($asset['deps']['style'][0])) {
							$asset_deps = array_merge($asset_deps, $asset['deps']['style']);
						}
						
						if (isset($asset['deps'][0]) and is_string($asset['deps'][0])) {
							$asset_deps = array_merge($asset_deps, $asset['deps']);
						}
						
					}

					foreach ($asset_deps as $key => $dep) {

						if (is_array($assets) and !empty($assets[$dep]) and !empty($assets[$dep][$type])) {

							$deps[$type][] = $prefix . $dep;

						} else {

							if ($type == 'script' and wp_script_is($dep, 'registered')) {
								$deps[$type][] = $dep;
							}

							if ($type == 'style' and wp_style_is($dep, 'registered')) {
								$deps[$type][] = $dep;
							}

						}

					}

				}

				$asset['deps'] = $deps;

			}

		}

	}

	return $asset;

}