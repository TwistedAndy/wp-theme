<?php
/**
 * Asset Management Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/**
 * Load assets from the plugins folder
 */
add_action('init', function() {

	$base = TW_ROOT . 'assets/plugins/';

	$files = scandir($base);

	$assets = tw_asset_list();

	if (is_array($files)) {

		$files = array_diff($files, ['..', '.']);

		foreach ($files as $name) {

			$filename = $base . $name . '/index.php';

			if (!is_file($filename)) {
				continue;
			}

			$asset = include($filename);

			if (!is_array($asset)) {
				continue;
			}

			if (!empty($assets[$name])) {

				if (is_callable($assets[$name]) or is_bool($assets[$name])) {
					$asset['display'] = $assets[$name];
				} elseif (is_array($assets[$name])) {
					$asset = wp_parse_args($assets[$name], $asset);
				}

			}

			$asset['directory'] = 'plugins/' . $name;

			$assets[$name] = $asset;

		}

	}

	$base = TW_ROOT . 'assets/build/';

	$files = scandir($base);

	if (!empty($assets['template']) and !empty($assets['template']['version'])) {
		$version = $assets['template']['version'];
		$need_version = false;
	} else {
		$need_version = true;
		$version = '';
	}

	if (is_array($files)) {

		foreach ($files as $file) {

			if (strpos($file, '.css') === false or strpos($file, 'block_') !== 0) {
				continue;
			}

			$name = substr($file, 6, -4) . '_box';

			if ($need_version) {
				$version = filemtime($base . $file);
			}

			$assets[$name] = [
				'style' => $file,
				'directory' => 'build',
				'version' => $version
			];

		}

	}

	if (empty($assets)) {
		return;
	}

	foreach ($assets as $name => $asset) {

		if (!is_string($name) or empty($asset)) {
			continue;
		}

		$asset = tw_asset_normalize($asset);

		if (!is_array($asset)) {
			continue;
		}

		$assets[$name] = $asset;

		if (!empty($asset['prefix'])) {
			$name = $asset['prefix'] . $name;
		}

		$deps = [];

		foreach (['script', 'style'] as $type) {

			if (empty($asset[$type]) or !is_array($asset[$type])) {
				continue;
			}

			$i = count($asset[$type]) - 1;

			$current_key = false;

			if (!empty($asset['deps'][$type]) and is_array($asset['deps'][$type])) {
				$deps[$type] = $asset['deps'][$type];
			} else {
				$deps[$type] = [];
			}

			foreach ($asset[$type] as $file) {

				$previous_key = $current_key;

				if ($i == 0) {
					$current_key = $name;
				} else {
					$current_key = $name . '-' . $i;
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

	tw_app_set('tw_registered_assets', $assets);

}, 30);


/**
 * Enqueue registered assets
 */
add_action('wp_enqueue_scripts', function() {

	$assets = tw_asset_list();

	if (empty($assets)) {
		return;
	}

	foreach ($assets as $name => $asset) {

		if (empty($asset['display'])) {
			continue;
		}

		if (is_callable($asset['display'])) {
			$asset['display'] = call_user_func($asset['display']);
		}

		if (!empty($asset['display'])) {
			tw_asset_enqueue($name);
		}

	}


}, 30);


/**
 * Get a list of currently registered assets
 *
 * @return array
 */
function tw_asset_list() {

	$assets = tw_app_get('tw_registered_assets');

	if (!is_array($assets)) {
		$assets = [];
	}

	return $assets;

}


/**
 * Register an array of assets
 *
 * @param array $assets
 *
 * @return void
 */
function tw_asset_register($assets) {

	if (!is_array($assets)) {
		return;
	}

	$data = tw_asset_list();

	foreach ($assets as $name => $asset) {
		if (!empty($data[$name]) and is_array($data[$name]) and (is_callable($asset) or is_bool($asset))) {
			$data[$name]['display'] = $asset;
		} else {
			$data[$name] = $asset;
		}
	}

	tw_app_set('tw_registered_assets', $data);

}


/**
 * Enqueue a single asset
 *
 * @param string $name Name of the asset
 */
function tw_asset_enqueue($name) {

	$asset_name = $name;

	$assets = tw_asset_list();

	if (!empty($assets[$name])) {

		$asset = $assets[$name];

		if (!empty($asset['prefix'])) {
			$asset_name = $asset['prefix'] . $name;
		}

		if (!empty($asset['localize'])) {

			if (is_callable($asset['localize'])) {
				$asset['localize'] = call_user_func($asset['localize']);
			}

			$object = $name;

			if (!empty($asset['object'])) {
				$object = $asset['object'];
			}

			if (is_array($asset['localize'])) {
				wp_localize_script($asset_name, $object, $asset['localize']);
			}

		}

	}

	if (wp_script_is($asset_name, 'registered')) {
		wp_enqueue_script($asset_name);
	}

	if (wp_style_is($asset_name, 'registered')) {
		wp_enqueue_style($asset_name);
	}

}


/**
 * Normalize an asset and check dependencies
 *
 * @param array $asset An array with asset configuration
 *
 * @return array
 */
function tw_asset_normalize($asset) {

	if (!is_array($asset)) {
		return $asset;
	}

	$base_url = get_template_directory_uri() . '/assets/';

	$defaults = [
		'deps' => [
			'style' => [],
			'script' => []
		],
		'style' => '',
		'script' => '',
		'footer' => true,
		'prefix' => 'tw_',
		'version' => null,
		'display' => false,
		'directory' => '',
		'localize' => [],
		'object' => ''
	];

	$asset = wp_parse_args($asset, $defaults);

	foreach (['style', 'script'] as $type) {

		if (!empty($asset[$type])) {

			if (is_string($asset[$type])) {
				$asset[$type] = [$asset[$type]];
			}

			foreach ($asset[$type] as $key => $link) {

				if (strpos($link, 'http') !== 0 and strpos($link, '//') !== 0) {

					$directory = '';

					if (!empty($asset['directory'])) {
						$directory = trailingslashit($asset['directory']);
					}

					$asset[$type][$key] = $base_url . $directory . $link;

				}

			}

		}

	}

	if (!empty($asset['deps'])) {

		if (is_string($asset['deps'])) {
			$asset['deps'] = [$asset['deps']];
		}

		if (is_array($asset['deps'])) {

			if (empty($asset['prefix'])) {
				$prefix = '';
			} else {
				$prefix = $asset['prefix'];
			}

			$deps = [];

			$assets = tw_asset_list();

			foreach (['script', 'style'] as $type) {

				if (isset($asset['deps'][$type]) and empty($asset['deps'][$type])) {
					continue;
				}

				$asset_deps = [];

				if (!empty($asset['deps'][$type])) {

					if (!is_array($asset['deps'][$type])) {
						$asset['deps'][$type] = [$asset['deps'][$type]];
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

				foreach ($asset_deps as $dep) {

					if ($assets and !empty($assets[$dep]) and !empty($assets[$dep][$type])) {

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

	return $asset;

}