<?php
/**
 * App Management Library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.1
 */

global $twee_cache;

$twee_cache = [];

/**
 * Declare theme support and load translations
 */
add_action('after_setup_theme', function() {

	$settings = tw_app_settings();

	load_theme_textdomain('twee', TW_ROOT . 'languages');

	if (!empty($settings['support'])) {
		foreach ($settings['support'] as $feature => $args) {
			if ($args) {
				add_theme_support($feature, $args);
			} else {
				add_theme_support($feature);
			}
		}
	}

	if (!empty($settings['menus'])) {
		register_nav_menus($settings['menus']);
	}

});


/**
 * Initialize sidebars and widgets
 */
add_action('widgets_init', function() {

	$settings = tw_app_settings();

	if (!empty($settings['sidebars'])) {
		foreach ($settings['sidebars'] as $sidebar) {
			register_sidebar($sidebar);
		}
	}

	if (!empty($settings['widgets'])) {
		foreach ($settings['widgets'] as $widget) {
			$class = '\\Twee\\Widgets\\' . $widget;
			if (class_exists($class)) {
				register_widget($class);
			}
		}
	}

});


/**
 * Initialize post types, taxonomies, etc.
 */
add_action('init', function() {

	$settings = tw_app_settings();

	if (!empty($settings['types'])) {
		foreach ($settings['types'] as $type => $args) {
			register_post_type($type, $args);
		}
	}

	if (!empty($settings['taxonomies'])) {
		foreach ($settings['taxonomies'] as $taxonomy => $args) {
			register_taxonomy($taxonomy, $args['types'], $args);
		}
	}

	if (file_exists(TW_ROOT . 'editor-style.css')) {
		add_editor_style('editor-style.css');
	}

});


/**
 *  Set a value to the runtime cache
 *
 * @param string $key
 * @param mixed  $value
 * @param string $group
 */
function tw_app_set($key, $value, $group = 'default') {

	global $twee_cache;

	if ($value === null and isset($twee_cache[$group]) and isset($twee_cache[$group][$key])) {

		unset($twee_cache[$group][$key]);

	} else {

		if (!isset($twee_cache[$group])) {
			$twee_cache[$group] = [];
		}

		$twee_cache[$group][$key] = $value;

	}

}


/**
 * Get a value from the runtime cache
 *
 * @param string $key
 * @param string $group
 * @param null   $default
 *
 * @return mixed
 */
function tw_app_get($key, $group = 'default', $default = null) {

	global $twee_cache;

	if (isset($twee_cache[$group]) and isset($twee_cache[$group][$key])) {
		return $twee_cache[$group][$key];
	} else {
		return $default;
	}

}


/**
 * Clear a runtime cache group
 *
 * @param $group
 *
 * @return void
 */
function tw_app_clear($group) {

	global $twee_cache;

	if (isset($twee_cache[$group])) {
		unset($twee_cache[$group]);
	}

}


/**
 * Get app settings
 *
 * @param string      $group
 * @param false|array $value
 *
 * @return array|array[]|false|mixed
 */
function tw_app_settings($group = false, $value = false) {

	$cache_key = 'tw_app_settings';

	$settings = tw_app_get($cache_key);

	if (!is_array($settings)) {
		$settings = [
			'support' => [],
			'menus' => [],
			'types' => [],
			'widgets' => [],
			'sidebars' => [],
			'taxonomies' => []
		];
	}

	if (!empty($group)) {

		if (is_array($value)) {
			$settings[$group] = $value;
			tw_app_set($cache_key, $settings);
		} elseif (!empty($settings[$group]) and is_array($settings[$group])) {
			$settings = $settings[$group];
		} else {
			$settings = [];
		}

	}

	return $settings;

}


/**
 * Get the database object
 *
 * @return wpdb
 */
function tw_app_database() {

	global $wpdb;

	if ($wpdb instanceof \wpdb) {

		return $wpdb;

	} else {

		$db_user = defined('DB_USER') ? DB_USER : '';
		$db_password = defined('DB_PASSWORD') ? DB_PASSWORD : '';
		$db_name = defined('DB_NAME') ? DB_NAME : '';
		$db_host = defined('DB_HOST') ? DB_HOST : '';

		return new \wpdb($db_user, $db_password, $db_name, $db_host);

	}

}


/**
 * Include PHP files from a folder
 *
 * @param string   $folder Full path to the folder
 * @param string[] $files  Array with file names, single file name or true to load all files
 *
 * @return void
 */
function tw_app_include($folder, $files = []) {

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
 * Render a template with specified data
 *
 * @param string                  $template Template part name
 * @param array|\WP_Post|\WP_Term $item     Array with data
 * @param string                  $folder   Folder with template part
 *
 * @return string
 */
function tw_app_template($template, $item = [], $folder = 'parts') {

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


/**
 * Remove an object filter from
 *
 * @param string $tag
 * @param string $class_name
 * @param string $method_name
 * @param int    $priority
 *
 * @return bool
 */
function tw_app_remove_filter($tag, $class_name = '', $method_name = '', $priority = 10) {

	global $wp_filter;

	if (!is_array($wp_filter) or empty($wp_filter[$tag]) or empty($wp_filter[$tag]->callbacks)) {
		return false;
	}

	$is_filter_removed = false;

	if (!empty($wp_filter[$tag]->callbacks[$priority])) {

		$filters = $wp_filter[$tag]->callbacks[$priority];

		foreach ($filters as $filter) {

			if (empty($filter['function']) or !is_array($filter['function']) or empty($filter['function'][0]) or empty($filter['function'][1])) {
				continue;
			}

			if ($filter['function'][1] !== $method_name) {
				continue;
			}

			if (!is_a($filter['function'][0], $class_name)) {
				continue;
			}

			$wp_filter[$tag]->remove_filter($tag, $filter['function'], $priority);

			$is_filter_removed = true;

		}

	}

	return $is_filter_removed;

}


/**
 * Declare supported features
 *
 * @param string|string[] $feature
 * @param array           $args
 */
function tw_app_features($feature, $args = []) {

	$settings = tw_app_settings('support');

	if (is_string($feature) and !isset($settings[$feature])) {

		$settings[$feature] = $args;

	} elseif (is_array($feature) and empty($args)) {

		$features = [];

		foreach ($feature as $key => $value) {

			if (is_numeric($key) and is_string($value)) {
				$features[$value] = [];
			} elseif (is_string($key) and is_array($value)) {
				$features[$key] = $value;
			}

		}

		$settings = array_merge($features, $settings);

		tw_app_settings('support', $settings);

	}


}


/**
 * Register navigation menus
 *
 * @param array $menus
 */
function tw_app_menus($menus) {

	if (!is_array($menus)) {
		return;
	}

	$settings = tw_app_settings('menus');

	foreach ($menus as $location => $description) {
		if (is_string($location) and is_string($description)) {
			$settings[$location] = $description;
		}
	}

	tw_app_settings('menus', $settings);

}


/**
 * Register a post type
 *
 * @param string $type
 * @param array  $args
 */
function tw_app_type($type, $args) {
	if (is_string($type) and is_array($args)) {
		$types = tw_app_settings('types');
		$types[$type] = $args;
		tw_app_settings('types', $types);
	}
}


/**
 * Register a taxonomy
 *
 * @param string          $name
 * @param string|string[] $types
 * @param array           $args
 */
function tw_app_taxonomy($name, $types, $args) {
	if (is_string($name) and is_array($args)) {
		$taxonomies = tw_app_settings('taxonomies');
		$args['types'] = $types;
		$taxonomies[$name] = $args;
		tw_app_settings('taxonomies', $taxonomies);
	}
}


/**
 * Register a sidebar position
 *
 * @param array $sidebar
 */
function tw_app_sidebar($sidebar) {
	if (is_array($sidebar)) {
		$sidebars = tw_app_settings('sidebars');
		$sidebars[] = $sidebar;
		tw_app_settings('sidebars', $sidebars);
	}
}


/**
 * Register a widget class
 *
 * @param string $widget
 */
function tw_app_widget($widget) {
	if (is_string($widget)) {
		$widgets = tw_app_settings('widgets');
		$widgets[] = $widget;
		tw_app_settings('widgets', $widgets);
	}
}