<?php
/**
 * Asset Management Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

/**
 * Register assets from the plugins and blocks folders
 */
add_action('init', function() {

	$base = TW_ROOT . 'assets/plugins/';

	$files = scandir($base);

	$assets = tw_app_get('registered', 'assets', []);

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

	$base = TW_ROOT . 'assets/build/blocks/';

	$files = scandir($base);

	if (is_array($files)) {

		foreach ($files as $file) {

			if (strpos($file, '.css') === false or strpos($file, '.map') > 0) {
				continue;
			}

			$name = str_replace('.css', '_box', $file);

			$asset = [
				'style' => $file,
				'prefix' => 'tw_block_',
				'directory' => 'build/blocks',
				'footer' => false
			];

			if (!empty($assets[$name])) {
				if (is_callable($assets[$name]) or is_bool($assets[$name])) {
					$asset['display'] = $assets[$name];
				} elseif (is_array($assets[$name])) {
					$asset = wp_parse_args($assets[$name], $asset);
				}
			}

			$assets[$name] = $asset;

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
			$asset_name = $asset['prefix'] . $name;
		} else {
			$asset_name = $name;
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

	tw_app_set('registered', $assets, 'assets');

	/**
	 * Automatically inject block styles
	 */
	if (!is_admin() and !wp_doing_ajax()) {
		ob_start('tw_asset_inject');
	}

}, 30);


/**
 * Add an asset placeholder
 */
add_action('wp_head', 'tw_asset_placeholder', 6);


/**
 * Print previously enqueued assets
 */
add_action('wp_head', 'tw_asset_print', 5);
add_action('wp_footer', 'tw_asset_print', 9);

function tw_asset_print() {

	$assets_registered = tw_app_get('registered', 'assets', []);
	$assets_localized = tw_app_get('localized', 'assets', []);
	$assets_enqueued = tw_app_get('enqueued', 'assets', []);
	$assets_printed = tw_app_get('printed', 'assets', []);

	foreach ($assets_registered as $name => $asset) {

		if (in_array($name, $assets_enqueued) or in_array($name, $assets_printed)) {
			continue;
		}

		if (isset($asset['display']) and is_callable($asset['display'])) {
			$asset['display'] = call_user_func($asset['display']);
		}

		if (empty($asset['display'])) {
			continue;
		}

		$assets_enqueued[] = $name;

	}

	$assets_enqueued = apply_filters('twee_asset_enqueue', $assets_enqueued, $assets_registered);

	$print_styles = [];
	$print_scripts = [];

	$filter = current_filter();

	foreach ($assets_enqueued as $name) {

		if (in_array($name, $assets_printed)) {
			continue;
		}

		if (isset($assets_registered[$name]) and is_array($assets_registered[$name])) {

			$asset = $assets_registered[$name];

			if ($filter == 'wp_head' and !empty($asset['footer'])) {
				continue;
			}

			$assets_printed[] = $name;

			if (!empty($asset['prefix'])) {
				$asset_name = $asset['prefix'] . $name;
			} else {
				$asset_name = $name;
			}

			if (!empty($asset['localize'])) {

				if (is_callable($asset['localize'])) {
					$asset['localize'] = call_user_func($asset['localize']);
				}

				if (!empty($asset['object'])) {
					$object = $asset['object'];
				} else {
					$object = $name;
				}

				if (is_array($asset['localize']) and !in_array($name, $assets_localized)) {
					wp_localize_script($asset_name, $object, $asset['localize']);
					$assets_localized[] = $name;
				}

			}

			$name = $asset_name;

		} else {

			$assets_printed[] = $name;

		}

		if (wp_script_is($name, 'registered')) {
			$print_scripts[] = $name;
		}

		if (wp_style_is($name, 'registered')) {
			$print_styles[] = $name;
		}

	}

	tw_app_set('localized', $assets_localized, 'assets');
	tw_app_set('printed', $assets_printed, 'assets');

	if ($print_scripts) {
		wp_scripts()->do_items($print_scripts);
	}

	if ($print_styles) {
		wp_styles()->do_items($print_styles);
	}

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

	$data = tw_app_get('registered', 'assets', []);

	foreach ($assets as $name => $asset) {
		if (!empty($data[$name]) and is_array($data[$name]) and (is_callable($asset) or is_bool($asset))) {
			$data[$name]['display'] = $asset;
		} else {
			$data[$name] = $asset;
		}
	}

	tw_app_set('registered', $data, 'assets');

}


/**
 * Enqueue one or a few assets
 *
 * @param string[]|string $name
 *
 * @return void
 */
function tw_asset_enqueue($name) {

	$enqueued = tw_app_get('enqueued', 'assets', []);
	$registered = tw_app_get('registered', 'assets', []);

	if (is_string($name) and isset($registered[$name])) {

		$enqueued[] = $name;

		tw_app_set('enqueued', array_unique($enqueued), 'assets');

	} elseif (is_array($name)) {

		foreach ($name as $asset_name) {
			if (is_string($asset_name) and isset($registered[$asset_name])) {
				$enqueued[] = $asset_name;
			}
		}

		tw_app_set('enqueued', array_unique($enqueued), 'assets');

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
	$base_path = TW_ROOT . 'assets/';

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

					if (!empty($asset['directory'])) {
						$directory = $asset['directory'] . '/';
					} else {
						$directory = '';
					}

					$filepath = $base_path . $directory . $link;

					if (!file_exists($filepath)) {
						unset($asset[$type][$key]);
						continue;
					}

					if (empty($asset['version'])) {
						$asset['version'] = substr(filemtime($filepath), 4);
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

			$assets = tw_app_get('registered', 'assets', []);

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


/**
 * Inject styles to the content at a placeholder position
 *
 * @param string $content
 *
 * @return string
 */
function tw_asset_inject($content) {

	$placeholder = tw_asset_placeholder(true);

	$assets = tw_app_get('registered', 'assets', []);

	if (empty($assets) or strpos($content, $placeholder) === false) {
		return $content;
	}

	preg_match_all('#[\'" =]+([a-z\-_]+?_box)#i', $content, $matches);

	if (!empty($matches) and !empty($matches[1]) and is_array($matches[1])) {

		$included = [];
		$required = [];

		foreach ($matches[1] as $token) {
			if (strpos($token, 'tw_block_') === 0) {
				$included[] = str_replace('tw_block_', '', $token);
			} else {
				$required[] = $token;
			}
		}

		$missing = array_diff($required, $included);

		$missing = array_unique($missing);

		$stylesheets = [];

		foreach ($missing as $name) {

			if (!empty($assets[$name]) and !empty($assets[$name]['style']) and is_array($assets[$name]['style'])) {

				if (!empty($assets[$name]['version'])) {
					$version = $assets[$name]['version'];
				} else {
					$version = '';
				}

				foreach ($assets[$name]['style'] as $style) {

					if ($version) {
						$style .= '?v=' . $version;
					}

					$stylesheets[] = '<link rel="stylesheet" id="tw_block_' . $name . '-css" href="' . esc_url($style) . '" media="all" />';

				}

			}

		}

		if ($stylesheets) {
			$replacement = implode("\n", $stylesheets);
		} else {
			$replacement = '';
		}

		$content = str_replace($placeholder, $replacement, $content);

	}

	return $content;

}


/**
 * Return or print the asset placeholder
 *
 * @return string|void
 */
function tw_asset_placeholder($return = false) {

	$placeholder = '<!-- TWEE_ASSET_PLACEHOLDER -->';

	if ($return) {
		return $placeholder;
	} else {
		echo $placeholder;
	}

}