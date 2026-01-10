<?php
/**
 * Asset Management Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */

/**
 * Bind the asset actions
 *
 * @return void
 */
function tw_asset_actions(): void
{
	add_action('wp_head', 'tw_asset_placeholder', 6);

	add_action('wp_head', 'tw_asset_init', 0);
	add_action('admin_enqueue_scripts', 'tw_asset_init', 0);

	add_action('wp_head', 'tw_asset_print', 5);
	add_action('wp_footer', 'tw_asset_print', 100);
}

add_action('init', 'tw_asset_actions', 20);


/**
 * Scan and register assets from the plugins and blocks folders
 * It's important to do this before the 'wp_enqueue_scripts' call
 */
function tw_asset_init(): void
{
	/**
	 * Register assets from the plugins folder
	 */
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

	/**
	 * Register styles from the blocks folder
	 */
	$base = TW_ROOT . 'assets/build/blocks/';

	$files = scandir($base);

	if (is_array($files)) {
		foreach ($files as $file) {
			if (strpos($file, '.css') === false or strpos($file, '.map') > 0) {
				continue;
			}

			$name = str_replace('.css', '_box', $file);

			$asset = [
				'style'     => $file,
				'prefix'    => 'tw_block_',
				'directory' => 'build/blocks',
				'footer'    => false
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

	/**
	 * Normalize assets and collect dependencies
	 */
	$available = [
		'style'  => [],
		'script' => [],
	];

	foreach ($assets as $name => $asset) {

		if (!is_string($name) or empty($asset)) {
			unset($assets[$name]);
			continue;
		}

		$asset = tw_asset_normalize($asset);

		if (!empty($asset['style'])) {
			$available['style'][$name] = true;
		}

		if (!empty($asset['script'])) {
			$available['script'][$name] = true;
		}

		$assets[$name] = $asset;

	}

	/**
	 * Check dependencies and register assets in WordPress
	 */
	foreach ($assets as $name => $asset) {

		/**
		 * Remove unavailable internal dependencies and prefix the internal ones
		 */
		foreach ($asset['deps'] as $type => $deps) {

			foreach ($deps as $index => $dep) {
				if (!empty($assets[$dep])) {
					if (empty($available[$type][$dep])) {
						unset($asset['deps'][$type][$index]);
					} else {
						$asset['deps'][$type][$index] = $assets[$dep]['prefix'] . $dep;
					}
				} elseif ($type == 'script' and !wp_script_is($dep, 'registered') and !wp_script_is($dep, 'enqueued')) {
					unset($asset['deps'][$type][$index]);
				} elseif ($type == 'style' and !wp_style_is($dep, 'registered') and !wp_style_is($dep, 'enqueued')) {
					unset($asset['deps'][$type][$index]);
				}
			}

			if (empty($asset['deps'][$type])) {
				unset($asset['deps'][$type]);
			}

			$assets[$name] = $asset;

		}

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

			if (!empty($asset['deps'][$type])) {
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
					wp_register_script($current_key, $file, $deps[$type], $asset['version'], [
						'in_footer' => $asset['footer'],
						'strategy'  => 'defer',
					]);
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
	if (!is_admin() and !wp_doing_ajax() and (!defined('WP_TESTS_CONFIG_FILE_PATH') or defined('TW_TEST_BUFFER_OVERRIDE'))) {
		ob_start('tw_asset_inject');
	}
}


/**
 * Print previously enqueued assets
 */
function tw_asset_print(): void
{
	$assets_registered = tw_app_get('registered', 'assets', []);
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

			if (!empty($asset['deps'])) {
				$deps = $asset['deps'];
			} else {
				$deps = [];
			}

			if (!empty($asset['localize']) or !empty($deps['script']) or !empty($deps['style']) or !empty($asset['inline'])) {
				tw_asset_localize($name);
			}

			if (!empty($deps['script']) and empty($asset['script'])) {
				$print_scripts = array_merge($print_scripts, $deps['script']);
			}

			if (!empty($deps['style']) and empty($asset['style'])) {
				$print_styles = array_merge($print_styles, $deps['style']);
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
function tw_asset_register(array $assets): void
{
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
 * @param bool            $instant
 *
 * @return void
 */
function tw_asset_enqueue($name, bool $instant = false): void
{
	$assets_registered = tw_app_get('registered', 'assets', []);

	if (is_array($name)) {
		foreach ($name as $asset_name) {
			tw_asset_enqueue($asset_name, $instant);
		}
	}

	if (!is_string($name) or empty($assets_registered[$name]) or !is_array($assets_registered[$name])) {
		return;
	}

	$assets_enqueued = tw_app_get('enqueued', 'assets', []);

	if ($instant) {

		$asset = $assets_registered[$name];

		if (!empty($asset['prefix'])) {
			$asset_name = $asset['prefix'] . $name;
		} else {
			$asset_name = $name;
		}

		if (!empty($asset['localize']) or (!empty($asset['deps']) and !empty($asset['deps']['script']))) {
			tw_asset_localize($name);
		}

		if (wp_script_is($asset_name, 'registered')) {
			wp_enqueue_script($asset_name);
		}

		if (wp_style_is($asset_name, 'registered')) {
			wp_enqueue_style($asset_name);
		}

		if (!empty($asset['deps'])) {
			$deps = $asset['deps'];
		} else {
			$deps = [];
		}

		if (!empty($deps['script']) and empty($asset['script'])) {
			foreach ($deps['script'] as $script) {
				wp_enqueue_script($script);
			}
		}

		if (!empty($deps['style']) and empty($asset['style'])) {
			foreach ($deps['style'] as $style) {
				wp_enqueue_style($style);
			}
		}

	}

	$assets_enqueued[] = $name;

	tw_app_set('enqueued', array_unique($assets_enqueued), 'assets');
}


/**
 * Localize an asset
 *
 * @param string $name
 *
 * @return void
 */
function tw_asset_localize(string $name): void
{
	$assets_registered = tw_app_get('registered', 'assets', []);
	$assets_localized = tw_app_get('localized', 'assets', []);
	$assets_map = tw_app_get('map', 'assets', false);

	if (!is_array($assets_map)) {

		$assets_map = [];

		foreach ($assets_registered as $key => $asset) {
			if (!empty($asset['prefix'])) {
				$assets_map[$asset['prefix'] . $key] = $key;
			} else {
				$assets_map[$key] = $key;
			}
		}

		tw_app_set('map', $assets_map, 'assets');

	}

	if (!empty($assets_map[$name])) {
		$name = (string) $assets_map[$name];
	}

	if (in_array($name, $assets_localized)) {
		return;
	}

	if (empty($assets_registered[$name])) {
		return;
	}

	$asset = $assets_registered[$name];

	if (!empty($asset['prefix'])) {
		$asset_name = $asset['prefix'] . $name;
	} else {
		$asset_name = $name;
	}

	if (!empty($asset['deps']) and !empty($asset['deps']['script'])) {
		foreach ($asset['deps']['script'] as $dependency) {
			tw_asset_localize($dependency);
		}
	}

	if (!is_array($asset) or (empty($asset['localize']) and empty($asset['inline']))) {
		$assets_localized[] = $name;
		tw_app_set('localized', $assets_localized, 'assets');

		return;
	}

	if (!empty($asset['localize'])) {

		if (is_callable($asset['localize'])) {
			$asset['localize'] = call_user_func($asset['localize']);
		}

		if (!empty($asset['object'])) {
			$object = $asset['object'];
		} else {
			$object = $asset_name;
		}

		if (is_array($asset['localize']) and wp_script_is($asset_name, 'registered')) {
			$assets_localized[] = $name;
			tw_app_set('localized', $assets_localized, 'assets');
			wp_localize_script($asset_name, $object, $asset['localize']);
		}

	}

	if (!empty($asset['inline'])) {
		if (is_callable($asset['inline'])) {
			$asset['inline'] = call_user_func($asset['inline']);
		}
		wp_add_inline_style($asset_name, $asset['inline']);
	}
}


/**
 * Normalize an asset and check dependencies
 *
 * @param array $asset An array with asset configuration
 *
 * @return array
 */
function tw_asset_normalize(array $asset): array
{
	$base_url = get_template_directory_uri() . '/assets/';
	$base_path = TW_ROOT . 'assets/';

	$defaults = [
		'deps'      => [
			'style'  => [],
			'script' => []
		],
		'style'     => '',
		'script'    => '',
		'footer'    => true,
		'prefix'    => 'tw_',
		'version'   => null,
		'display'   => false,
		'directory' => '',
		'localize'  => [],
		'inline'    => '',
		'object'    => ''
	];

	$asset = wp_parse_args($asset, $defaults);

	$deps = [];

	if (!empty($asset['deps'])) {
		if (is_string($asset['deps'])) {
			$deps = [
				'script' => $asset['deps'],
				'style'  => $asset['deps'],
			];
		} elseif (is_array($asset['deps'])) {
			if (isset($asset['deps'][0])) {
				$deps['style'] = $asset['deps'];
				$deps['script'] = $asset['deps'];
			} else {
				if (!empty($asset['deps']['style'])) {
					$deps['style'] = $asset['deps']['style'];
				}
				if (!empty($asset['deps']['script'])) {
					$deps['script'] = $asset['deps']['script'];
				}
			}
		}
	}

	$asset['deps'] = $deps;

	foreach (['style', 'script'] as $type) {

		if (empty($asset[$type])) {
			continue;
		}

		if (is_string($asset[$type])) {
			$asset[$type] = [$asset[$type]];
		}

		$timestamp = 0;

		foreach ($asset[$type] as $key => $link) {

			if (!str_starts_with($link, 'http') and !str_starts_with($link, '//')) {

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
					$timestamp = max(filemtime($filepath), $timestamp);
				}

				$asset[$type][$key] = $base_url . $directory . $link;

			}

		}

		if ($timestamp > 0) {
			$asset['version'] = substr($timestamp, 4);
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
function tw_asset_inject($content): string
{
	$placeholder = tw_asset_placeholder(true);

	$assets = tw_app_get('registered', 'assets', []);

	if (empty($assets) or strpos($content, $placeholder) === false) {
		return $content;
	}

	$replacement = '';

	$file = TW_ROOT . 'assets/styles/base/fonts.scss';

	if (file_exists($file)) {

		$fonts = file_get_contents($file);

		if ($fonts) {
			$folder = str_replace(TW_HOME, '', TW_URL);
			$replacement = str_replace(['../fonts/', "\t", "\n", "\r", '  '], [$folder . 'assets/fonts/', '', '', '', ' '], $fonts) . "\n";
		}

	}

	$file = TW_ROOT . 'assets/build/properties.css';

	if (file_exists($file)) {
		$replacement .= file_get_contents($file);
	}

	$replacement = apply_filters('twee_asset_critical', $replacement);

	if ($replacement) {
		$replacement = "\n<style>" . $replacement . "</style>\n";
	}

	preg_match_all('#[\'" =]+([a-z\-_]+?_box)#i', $content, $matches);

	if (empty($matches) or empty($matches[1]) or !is_array($matches[1])) {
		return str_replace($placeholder, $replacement, $content);
	}

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

	if (empty($missing)) {
		return str_replace($placeholder, $replacement, $content);
	}

	$intersect = array_intersect(['header_box', 'modal_box'], $missing);

	if ($intersect) {
		$missing = array_merge($intersect, $missing);
	}

	$missing = array_unique($missing);

	$preload = [];
	$stylesheets = [];

	$limit = apply_filters('twee_asset_limit', 4);

	foreach ($missing as $index => $name) {

		if (empty($assets[$name]) or empty($assets[$name]['style']) or !is_array($assets[$name]['style'])) {
			continue;
		}

		$version = $assets[$name]['version'] ?? '';

		foreach ($assets[$name]['style'] as $style) {

			if ($version) {
				$style .= '?v=' . $version;
			}

			if ($index <= $limit) {
				$stylesheets[] = '<link rel="stylesheet" href="' . esc_url($style) . '" media="all" />';
			} else {
				$stylesheets[] = '<link rel="preload" as="style" href="' . esc_url($style) . '" onload="this.onload=null;this.rel=\'stylesheet\'" />';
				$preload[] = '<link rel="stylesheet" href="' . esc_url($style) . '" media="all" />';
			}

		}

	}

	if ($stylesheets) {
		$replacement .= "\n" . implode("\n", $stylesheets) . "\n";
	}

	if ($preload) {
		$replacement .= "<noscript>\n" . implode("\n", $preload) . "\n</noscript>\n";
	}

	return str_replace($placeholder, $replacement, $content);
}


/**
 * Return or print the asset placeholder
 *
 * @return string|void
 */
function tw_asset_placeholder(bool $return = false)
{
	$placeholder = '<!-- TWEE_ASSET_PLACEHOLDER -->';

	if ($return) {
		return $placeholder;
	}

	echo $placeholder;
}