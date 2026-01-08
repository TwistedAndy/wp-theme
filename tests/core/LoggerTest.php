<?php

/**
 * Logger Tests using WP Integration Suite
 */
class LoggerTest extends WP_UnitTestCase {

	/**
	 * Run once before the class tests start.
	 */
	public static function set_up_before_class(): void
	{
		parent::set_up_before_class();
	}

	/**
	 * Run once after all class tests end.
	 */
	public static function tear_down_after_class(): void
	{
		self::cleanup_test_logs();
		parent::tear_down_after_class();
	}

	/**
	 * Run before each test.
	 */
	public function set_up(): void
	{
		parent::set_up();
	}

	/**
	 * Run after each test.
	 */
	public function tear_down(): void
	{
		self::cleanup_test_logs();
		parent::tear_down();
	}

	/**
	 * Assertions before test execution.
	 */
	public function assert_pre_conditions(): void
	{
		parent::assert_pre_conditions();
	}

	/**
	 * Assertions after test execution.
	 */
	public function assert_post_conditions(): void
	{
		parent::assert_post_conditions();
	}

	/**
	 * Helper to delete the test logs folder.
	 * Made static to be callable from tear_down_after_class.
	 */
	private static function cleanup_test_logs(): void
	{
		$uploads = wp_get_upload_dir();
		$basedir = $uploads['basedir'] ?? get_template_directory();
		$folder = $basedir . '/cache/logs/';

		if (is_dir($folder)) {
			$files = glob($folder . '*');
			foreach ($files as $file) {
				if (is_file($file)) {
					@unlink($file);
				}
			}
			@rmdir($folder);
		}
	}

	/**
	 * Test filename generation logic.
	 */
	public function test_filename_generation(): void
	{
		$timestamp = strtotime('2025-01-01 12:00:00');
		$filename = tw_logger_filename('error', 'scope-time', $timestamp);
		$this->assertStringContainsString('2025_01_01', $filename);

		$filename_zero = tw_logger_filename('debug', 'scope-zero', 0);
		$this->assertStringNotContainsString(date('Y_m_d'), $filename_zero);
		$this->assertStringEndsWith('twee_log_scope-zero_debug.log', $filename_zero);

		$filename_empty_type = tw_logger_filename('', 'scope-no-type', 0);
		$this->assertStringEndsWith('twee_log_scope-no-type.log', $filename_empty_type);
	}

	/**
	 * Test fallback folder when uploads dir is missing.
	 */
	public function test_filename_fallback_directory(): void
	{
		add_filter('upload_dir', function($uploads) {
			$uploads['basedir'] = '';

			return $uploads;
		});

		$filename = tw_logger_filename('info', 'scope-fallback', 0);

		remove_all_filters('upload_dir');

		$expected_root = get_template_directory();
		$this->assertStringStartsWith($expected_root, $filename);
	}

	/**
	 * Test default logging.
	 */
	public function test_default_logging_info(): void
	{
		if (function_exists('wc_get_logger')) {
			$this->markTestSkipped('WooCommerce active; file logging logic bypassed.');
		}

		$message = 'Info Message ' . rand(1000, 9999);
		$scope = 'unit-test-default';

		tw_logger_info($message, $scope);

		$file = tw_logger_filename('info', $scope, 0);
		$this->assertFileExists($file);

		$content = file_get_contents($file);
		$this->assertStringContainsString($message, $content);
	}

	/**
	 * Test dated logging explicitly.
	 */
	public function test_dated_logging_read_write(): void
	{
		if (function_exists('wc_get_logger')) {
			$this->markTestSkipped('WooCommerce active');
		}

		$message = 'Dated Message ' . rand(1000, 9999);
		$scope = 'unit-test-dated';
		$now = time();

		tw_logger_write($message, 'info', $scope, $now);

		$file = tw_logger_filename('info', $scope, $now);
		$this->assertFileExists($file);

		$logs = tw_logger_read('info', $scope, $now);
		$this->assertNotEmpty($logs);

		$last_entry = end($logs);
		if (empty($last_entry)) {
			$last_entry = prev($logs);
		}
		$this->assertStringContainsString($message, $last_entry);
	}

	/**
	 * Test read fallback logic.
	 */
	public function test_read_default_time_fallback(): void
	{
		if (function_exists('wc_get_logger')) {
			$this->markTestSkipped('WooCommerce active');
		}

		$scope = 'read-fallback';
		$now = time();
		tw_logger_write('Fallback Test', 'info', $scope, $now);

		$logs = tw_logger_read('info', $scope, 0);

		$this->assertNotEmpty($logs);
		$this->assertStringContainsString('Fallback Test', implode('', $logs));
	}

	/**
	 * Test WooCommerce Logger integration using Mocks.
	 */
	public function test_woocommerce_logger_integration(): void
	{
		// Mock the wc_get_logger function if it doesn't exist
		if (!function_exists('wc_get_logger')) {
			function wc_get_logger()
			{
				global $tw_test_wc_logger;
				if (!isset($tw_test_wc_logger)) {
					$tw_test_wc_logger = new WC_Logger_Mock();
				}

				return $tw_test_wc_logger;
			}
		}

		global $tw_test_wc_logger;
		$tw_test_wc_logger = new WC_Logger_Mock(); // Reset for this test

		$scope = 'wc-test-scope';
		$ctx = ['source' => $scope];

		// Test Error
		tw_logger_write('Error Msg', 'error', $scope);
		$this->assertEquals('Error Msg', $tw_test_wc_logger->logs['error'][0][0]);
		$this->assertEquals($ctx, $tw_test_wc_logger->logs['error'][0][1]);

		// Test Info
		tw_logger_write('Info Msg', 'info', $scope);
		$this->assertEquals('Info Msg', $tw_test_wc_logger->logs['info'][0][0]);

		// Test Debug
		tw_logger_write('Debug Msg', 'debug', $scope);
		$this->assertEquals('Debug Msg', $tw_test_wc_logger->logs['debug'][0][0]);

		// Test Notice (Else case)
		tw_logger_write('Notice Msg', 'custom-type', $scope);
		$this->assertEquals('Notice Msg', $tw_test_wc_logger->logs['notice'][0][0]);
	}

	/**
	 * Test error wrapper with WC logic if mock exists.
	 */
	public function test_logger_error_wrapper(): void
	{
		if (function_exists('wc_get_logger')) {
			global $tw_test_wc_logger;
			$tw_test_wc_logger = new WC_Logger_Mock();

			tw_logger_error('Wrapper Error', 'wrapper-scope');

			$this->assertEquals('Wrapper Error', $tw_test_wc_logger->logs['error'][0][0]);

			return;
		}

		// Fallback to standard file test if mock wasn't loaded (unlikely given previous test runs first)
		$message = 'Critical Error Test';
		$scope = 'error-scope';

		tw_logger_error($message, $scope);

		$file = tw_logger_filename('error', $scope, 0);
		$this->assertFileExists($file);

		$content = file_get_contents($file);
		$this->assertStringContainsString($message, $content);
	}

}

/**
 * Mock Class for WooCommerce Logger
 * Defined outside the test class to avoid nesting errors.
 */
if (!class_exists('WC_Logger_Mock')) {
	class WC_Logger_Mock {

		public $logs = [];

		public function error($message, $context)
		{
			$this->logs['error'][] = [$message, $context];
		}

		public function info($message, $context)
		{
			$this->logs['info'][] = [$message, $context];
		}

		public function debug($message, $context)
		{
			$this->logs['debug'][] = [$message, $context];
		}

		public function notice($message, $context)
		{
			$this->logs['notice'][] = [$message, $context];
		}

	}
}