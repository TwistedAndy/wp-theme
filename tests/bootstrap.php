<?php
/**
 * PHPUnit Bootstrap for WordPress Integration Tests
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

$wp_tests_dir = dirname(__DIR__) . '/vendor/wp-phpunit/wp-phpunit';

if (!defined('WP_TESTS_CONFIG_FILE_PATH')) {
	define('WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/config.php');
}

if (!file_exists($wp_tests_dir . '/includes/functions.php')) {
	echo "Error: Could not find WP Test Library at: $wp_tests_dir" . PHP_EOL;
	exit(1);
}

require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Manually load the theme environment.
 */
function _manually_load_theme()
{
	// Theme Configuration Constants
	if (!defined('TW_THEME_GAP')) {
		define('TW_THEME_GAP', 20);
	}
	if (!defined('TW_THEME_WIDTH')) {
		define('TW_THEME_WIDTH', 1420);
	}

	// Path Constants
	if (!defined('TW_ROOT')) {
		define('TW_ROOT', dirname(__DIR__) . '/theme/');
	}
	if (!defined('TW_INC')) {
		define('TW_INC', TW_ROOT . 'includes/');
	}

	// URL Constants
	// We use WP functions if available, mimicking functions.php behavior
	if (!defined('TW_HOME')) {
		define('TW_HOME', rtrim(get_site_url(), '/\\'));
	}

	if (!defined('TW_URL')) {
		// In tests, get_stylesheet_directory_uri() might behave differently if the theme isn't active,
		// but usually it works if WP is loaded. We provide a safe fallback just in case.
		$theme_url = function_exists('get_stylesheet_directory_uri') ? get_stylesheet_directory_uri() : TW_HOME . '/wp-content/themes/theme';
		define('TW_URL', $theme_url . '/');
	}

	// Utility Constants
	if (!defined('TW_CACHE')) {
		define('TW_CACHE', true);
	}

	if (!defined('TW_FOLDER')) {
		$url = parse_url(TW_HOME);
		define('TW_FOLDER', (is_array($url) && !empty($url['path'])) ? $url['path'] : '');
	}

	// Load the main app file first (required for tw_app_include)
	$app_file = TW_INC . 'core/app.php';

	if (file_exists($app_file)) {
		require_once $app_file;
	} else {
		trigger_error("Core app file not found: $app_file", E_USER_WARNING);

		return;
	}

	// Load Core and Theme modules dynamically
	tw_app_include(TW_INC . 'core');
	tw_app_include(TW_INC . 'theme');
}

tests_add_filter('muplugins_loaded', '_manually_load_theme');

require $wp_tests_dir . '/includes/bootstrap.php';