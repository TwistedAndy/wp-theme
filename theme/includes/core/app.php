<?php
/**
 * App Management Module
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */

namespace Twee;

class App {

	protected static $instances = [];

	protected $settings = [
		'support' => [],
		'menus' => [],
		'types' => [],
		'widgets' => [],
		'sidebars' => [],
		'taxonomies' => []
	];


	protected function __construct() {
		add_action('after_setup_theme', [$this, 'actionSetup']);
		add_action('widgets_init', [$this, 'actionWidgets']);
		add_action('init', [$this, 'actionInit']);
	}


	/**
	 * Get the instance of selected module
	 *
	 * @param string $module Class name without namespace
	 * @param array  $args   Array with arguments for constructor
	 *
	 * @return object
	 */
	public static function get($module = 'App', $args = []) {

		$key = strtolower($module);

		if (empty(self::$instances[$key])) {

			$module = '\\Twee\\' . $module;

			$module = apply_filters('twee_module_class', $module);

			if (class_exists($module)) {

				$args = apply_filters('twee_module_args', $args);

				self::$instances[$key] = new $module($args);

			}

		}

		return self::$instances[$key];

	}


	/**
	 * Get the App object
	 *
	 * @return App
	 */
	public static function getApp() {

		if (empty(self::$instances['app'])) {
			self::$instances['app'] = self::get('App');
		}

		return self::$instances['app'];

	}


	/**
	 * Get the Assets object
	 *
	 * @return Assets
	 */
	public static function getAssets() {

		if (empty(self::$instances['assets'])) {
			self::$instances['assets'] = self::get('Assets');
		}

		return self::$instances['assets'];

	}


	/**
	 * Get the Content object
	 *
	 * @return Content
	 */
	public static function getContent() {

		if (empty(self::$instances['content'])) {
			self::$instances['content'] = self::get('Content');
		}

		return self::$instances['content'];

	}


	/**
	 * Get the image object
	 *
	 * @return Image
	 */
	public static function getImage() {

		if (empty(self::$instances['image'])) {
			self::$instances['image'] = self::get('Image');
		}

		return self::$instances['image'];

	}


	/**
	 * Get the terms object
	 *
	 * @return Terms
	 */
	public static function getTerms() {

		if (empty(self::$instances['terms'])) {
			self::$instances['terms'] = self::get('Terms');
		}

		return self::$instances['terms'];

	}


	/**
	 * Get the logger object
	 *
	 * @param string $type
	 *
	 * @return Terms
	 */
	public static function getLogger($type = 'theme') {

		if (empty(self::$instances['logger_' . $type])) {
			self::$instances['logger_' . $type] = new Logger($type);
		}

		return self::$instances['logger_' . $type];

	}


	/**
	 * Action Handlers
	 */
	public function actionInit() {

		if (!empty($this->settings['types'])) {
			foreach ($this->settings['types'] as $type => $args) {
				register_post_type($type, $args);
			}
		}

		if (!empty($this->settings['taxonomies'])) {
			foreach ($this->settings['taxonomies'] as $taxonomy => $args) {
				register_taxonomy($taxonomy, $args['types'], $args);
			}
		}

		if (file_exists(TW_ROOT . 'editor-style.css')) {
			add_editor_style('editor-style.css');
		}

	}


	public function actionSetup() {

		load_theme_textdomain('twee', TW_ROOT . 'languages');

		if (!empty($this->settings['support'])) {
			foreach ($this->settings['support'] as $feature => $args) {
				if ($args) {
					add_theme_support($feature, $args);
				} else {
					add_theme_support($feature);
				}
			}
		}

		if (!empty($this->settings['menus'])) {
			register_nav_menus($this->settings['menus']);
		}

	}


	public function actionWidgets() {

		if (!empty($this->settings['sidebars'])) {
			foreach ($this->settings['sidebars'] as $sidebar) {
				register_sidebar($sidebar);
			}
		}

		if (!empty($this->settings['widgets'])) {
			foreach ($this->settings['widgets'] as $widget) {
				$class = '\\Twee\\Widgets\\' . $widget;
				if (class_exists($class)) {
					register_widget($class);
				}
			}
		}

	}


	/**
	 * Include all or selected PHP files from the folder
	 *
	 * @param string $folder Full path to the folder
	 * @param array  $files  Array with file names, single file name or true to load all files
	 *
	 * @return void
	 */
	public function includeFolder($folder, $files = []) {

		if (!is_dir($folder)) {
			return;
		}

		if (empty($files)) {

			$files = scandir($folder);

			if (is_array($files)) {

				$list = [];

				foreach ($files as $file) {

					if (strpos($file, '.php') !== false) {

						$list[] = str_replace('.php', '', $file);

					}

				}

				$files = $list;

			}

		}

		foreach ($files as $file) {

			$filename = $folder . DIRECTORY_SEPARATOR . $file . '.php';

			if (is_readable($filename)) {
				include_once($filename);
			}

		}

	}


	public function addSupport($feature, $args = []) {

		if (is_string($feature) and !isset($this->settings['support'][$feature])) {

			$this->settings['support'][$feature] = $args;

		} elseif (is_array($feature) and empty($args)) {

			$data = [];

			foreach ($feature as $key => $value) {

				if (is_numeric($key) and is_string($value)) {
					$data[$value] = [];
				} elseif (is_string($key) and is_array($value)) {
					$data[$key] = $value;
				}

			}

			$this->settings['support'] = array_merge($data, $this->settings['support']);

		}

	}


	public function registerMenu($menus) {
		if (is_array($menus)) {
			foreach ($menus as $location => $description) {
				if (is_string($location) and is_string($description)) {
					$this->settings['menus'][$location] = $description;
				}
			}
		}
	}


	public function registerType($type, $args) {
		if (is_string($type) and is_array($args)) {
			$this->settings['types'][$type] = $args;
		}
	}


	public function registerTaxonomy($name, $types, $args) {
		if (is_string($name) and is_array($args)) {
			$args['types'] = $types;
			$this->settings['taxonomies'][$name] = $args;
		}
	}


	public function registerSidebar($sidebar) {
		if (is_array($sidebar)) {
			$this->settings['sidebars'][] = $sidebar;
		}
	}


	public function registerWidget($widget) {
		if (is_string($widget)) {
			$this->settings['widgets'][] = $widget;
		}
	}


	/**
	 * Render a template with specified data
	 *
	 * @param string                  $name   Template part name
	 * @param array|\WP_Post|\WP_Term $item   Array with data
	 * @param string                  $folder Folder with template part
	 *
	 * @return string
	 */
	public function template($name, $item = [], $folder = 'parts') {

		ob_start();

		if ($folder) {
			$folder = untrailingslashit($folder) . DIRECTORY_SEPARATOR;
		} else {
			$folder = '';
		}

		$filename = TW_ROOT . $folder . $name . '.php';

		if (is_array($item)) {
			extract($item);
		}

		if (file_exists($filename)) {
			include $filename;
		}

		$result = ob_get_contents();

		if (empty($result)) {
			$result = '';
		}

		ob_end_clean();

		return $result;

	}

}