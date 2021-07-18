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
	 * Get an instance of module
	 *
	 * @param string $module Class name without namespace
	 * @param array  $args   Array with arguments for constructor
	 *
	 * @return object
	 */
	public static function getModule($module = 'App', $args = []) {

		$key = strtolower($module);

		if (empty(self::$instances[$key])) {

			$module = '\\Twee\\' . $module;

			if (class_exists($module)) {

				self::$instances[$key] = new $module($args);

			}

		}

		return self::$instances[$key];

	}


	/**
	 * Get an app object
	 *
	 * @return App
	 */
	public static function getApp() {

		if (empty(self::$instances['app'])) {
			self::$instances['app'] = self::getModule('App');
		}

		return self::$instances['app'];

	}


	/**
	 * Get an assets object
	 *
	 * @return Assets
	 */
	public static function getAssets() {

		if (empty(self::$instances['assets'])) {
			self::$instances['assets'] = self::getModule('Assets');
		}

		return self::$instances['assets'];

	}


	/**
	 * Get a content object
	 *
	 * @return Content
	 */
	public static function getContent() {

		if (empty(self::$instances['content'])) {
			self::$instances['content'] = self::getModule('Content');
		}

		return self::$instances['content'];

	}


	/**
	 * Get an image object
	 *
	 * @return Image
	 */
	public static function getImage() {

		if (empty(self::$instances['image'])) {
			self::$instances['image'] = self::getModule('Image');
		}

		return self::$instances['image'];

	}


	/**
	 * Get a terms object
	 *
	 * @return Terms
	 */
	public static function getTerms() {

		if (empty(self::$instances['terms'])) {
			self::$instances['terms'] = self::getModule('Terms');
		}

		return self::$instances['terms'];

	}


	/**
	 * Get a logger object
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
	 * Theme initialization actions
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
	 * @param string   $folder Full path to the folder
	 * @param string[] $files  Array with file names, single file name or true to load all files
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


	/**
	 * Declare supported features
	 *
	 * @param string|string[] $feature
	 * @param array           $args
	 */
	public function registerFeatures($feature, $args = []) {

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


	/**
	 * Register navigation menus
	 *
	 * @param array $menus
	 */
	public function registerMenu($menus) {
		if (is_array($menus)) {
			foreach ($menus as $location => $description) {
				if (is_string($location) and is_string($description)) {
					$this->settings['menus'][$location] = $description;
				}
			}
		}
	}


	/**
	 * Register a post type
	 *
	 * @param string $type
	 * @param array  $args
	 */
	public function registerType($type, $args) {
		if (is_string($type) and is_array($args)) {
			$this->settings['types'][$type] = $args;
		}
	}


	/**
	 * Register a taxonomy
	 *
	 * @param string          $name
	 * @param string|string[] $types
	 * @param array           $args
	 */
	public function registerTaxonomy($name, $types, $args) {
		if (is_string($name) and is_array($args)) {
			$args['types'] = $types;
			$this->settings['taxonomies'][$name] = $args;
		}
	}


	/**
	 * Register a sidebar position
	 *
	 * @param array $sidebar
	 */
	public function registerSidebar($sidebar) {
		if (is_array($sidebar)) {
			$this->settings['sidebars'][] = $sidebar;
		}
	}


	/**
	 * Register a widget class
	 *
	 * @param string $widget
	 */
	public function registerWidget($widget) {
		if (is_string($widget)) {
			$this->settings['widgets'][] = $widget;
		}
	}


	/**
	 * Render a template with specified data
	 *
	 * @param string                  $template Template part name
	 * @param array|\WP_Post|\WP_Term $item     Array with data
	 * @param string                  $folder   Folder with template part
	 *
	 * @return string
	 */
	public function renderTemplate($template, $item = [], $folder = 'parts') {

		ob_start();

		if ($folder) {
			$folder = untrailingslashit($folder) . DIRECTORY_SEPARATOR;
		} else {
			$folder = '';
		}

		$filename = TW_ROOT . $folder . $template . '.php';

		if (is_array($item)) {
			extract($item);
		}

		if (file_exists($filename)) {
			include $filename;
		}

		return ob_get_clean();

	}

}