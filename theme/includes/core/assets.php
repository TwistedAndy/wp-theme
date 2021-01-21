<?php
/**
 * Asset Management Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */

namespace Twee;

class Assets {

	protected $assets = [];


	public function __construct() {

		add_action('init', [$this, 'actionRegister'], 30);

		add_action('wp_enqueue_scripts', [$this, 'actionEnqueue'], 30);

	}


	/**
	 * Register all custom assets
	 */
	public function actionRegister() {

		$base = TW_ROOT . 'assets' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;

		$directories = scandir($base);

		if (is_array($directories)) {

			$directories = array_diff($directories, ['..', '.']);

			foreach ($directories as $name) {

				$filename = $base . $name . DIRECTORY_SEPARATOR . 'index.php';

				if (is_file($filename)) {

					$asset = include($filename);

					if (is_array($asset)) {

						if (!empty($this->assets[$name])) {

							if (is_callable($this->assets[$name]) or is_bool($this->assets[$name])) {
								$asset['display'] = $this->assets[$name];
							} elseif (is_array($this->assets[$name])) {
								$asset = wp_parse_args($this->assets[$name], $asset);
							}

						}

						$asset['directory'] = 'plugins/' . $name;

						$this->add($name, $asset);

					}

				}

			}

		}

		foreach ($this->assets as $name => $asset) {
			$this->register($name, $asset);
		}

	}


	/**
	 * Enqueue all registered assets
	 */
	public function actionEnqueue() {

		if (!empty($this->assets)) {

			foreach ($this->assets as $name => $asset) {

				if (!empty($asset['display'])) {

					if (is_callable($asset['display'])) {
						$asset['display'] = call_user_func($asset['display']);
					}

					if ($asset['display']) {
						$this->enqueue($name);
					}

				}

			}

		}

	}


	/**
	 * Add an asset configuration
	 *
	 * @param string              $name  Unique name of the asset
	 * @param array|callable|bool $asset An array with asset configuration, callable, or bool
	 */
	public function add($name, $asset) {
		if (is_string($name) and (is_array($asset) or is_callable($asset) or is_bool($asset))) {
			$this->assets[$name] = $asset;
		}
	}


	/**
	 * Register a single asset
	 *
	 * @param string $name  Unique name of the asset
	 * @param array  $asset An array with the asset configuration
	 */
	public function register($name, $asset) {

		if (is_string($name) and is_array($asset)) {

			$asset = $this->normalize($asset);

			$this->assets[$name] = $asset;

			$asset = apply_filters('twee_asset_register', $asset, $name);

			if (!empty($asset['prefix'])) {
				$name = $asset['prefix'] . $name;
			}

			$deps = [];

			foreach (['script', 'style'] as $type) {

				if (!empty($asset[$type]) and is_array($asset[$type])) {

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

		}

	}


	/**
	 * Enqueue a single asset
	 *
	 * @param string $name Name of the asset
	 */
	public function enqueue($name) {

		$asset_name = $name;

		if (!empty($this->assets[$name])) {

			$asset = apply_filters('twee_asset_enqueue', $this->assets[$name], $name);

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

	}


	/**
	 * Normalize an asset and check dependencies
	 *
	 * @param array $asset An array with asset configuration
	 *
	 * @return array
	 */
	public function normalize($asset) {

		if (is_array($asset)) {

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
				'localize' => []
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

					foreach (['script', 'style'] as $type) {

						if (isset($asset['deps'][$type]) and empty($asset['deps'][$type])) {
							continue;
						}

						$asset_deps = [];

						if (!empty($asset['deps'][$type]) and is_array($asset['deps'][$type])) {

							if (is_string($asset['deps'][$type])) {
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

						foreach ($asset_deps as $key => $dep) {

							if (is_array($this->assets) and !empty($this->assets[$dep]) and !empty($this->assets[$dep][$type])) {

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

}