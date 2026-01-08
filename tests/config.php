<?php
/**
 * Test Configuration File
 *
 * This file configures the WordPress test suite environment.
 * It is loaded by the WP Test Library before the test runner starts.
 */

/*
 * Path to the WordPress codebase you are testing.
 */
if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__DIR__, 3) . '/');
}

/*
 * Test Database Configuration
 * These settings are used to create a fresh database for testing.
 * WARNING: This database will be wiped during tests!
 */
if (!defined('DB_NAME')) {
	define('DB_NAME', 'wordpress_test');
}
if (!defined('DB_USER')) {
	define('DB_USER', 'root');
}
if (!defined('DB_PASSWORD')) {
	define('DB_PASSWORD', '0000');
}
if (!defined('DB_HOST')) {
	define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_CHARSET')) {
	define('DB_CHARSET', 'utf8');
}
if (!defined('DB_COLLATE')) {
	define('DB_COLLATE', '');
}

/*
 * Test Site Configuration
 * Default settings for the test installation.
 */
if (!defined('WP_TESTS_DOMAIN')) {
	define('WP_TESTS_DOMAIN', 'example.org');
}
if (!defined('WP_TESTS_EMAIL')) {
	define('WP_TESTS_EMAIL', 'admin@example.org');
}
if (!defined('WP_TESTS_TITLE')) {
	define('WP_TESTS_TITLE', 'Test Blog');
}

/*
 * PHP Binary
 * Used by WP-CLI and other tools during testing.
 */
if (!defined('WP_PHP_BINARY')) {
	define('WP_PHP_BINARY', 'php');
}

/*
 * Table Prefix
 * Must be defined to avoid warnings during test execution.
 */
$table_prefix = 'wptests_';