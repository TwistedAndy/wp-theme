<?php

/**
 * Logger Tests using WP Integration Suite
 */
class LoggerTest extends WP_UnitTestCase {

	/**
	 * Setup method.
	 */
	public function setUp(): void
	{
		parent::setUp();
	}

	/**
	 * Teardown method to clean up files.
	 */
	public function tearDown(): void
	{
		parent::tearDown();
		$this->cleanup_test_logs();
	}

	/**
	 * Helper to delete the test logs folder.
	 */
	private function cleanup_test_logs()
	{
		$uploads = wp_get_upload_dir();

		// Fallback if uploads dir isn't set yet
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
	public function test_filename_generation()
	{
		// Check that specific timestamps generate dated filenames
		$timestamp = strtotime('2025-01-01 12:00:00');
		$filename = tw_logger_filename('error', 'scope-time', $timestamp);
		$this->assertStringContainsString('2025_01_01', $filename);

		// Check that zero/default timestamp generates a non-dated filename
		$filename_zero = tw_logger_filename('debug', 'scope-zero', 0);
		$this->assertStringNotContainsString(date('Y_m_d'), $filename_zero);
		$this->assertStringEndsWith('twee_log_scope-zero_debug.log', $filename_zero);
	}

	/**
	 * Test default logging which creates non-dated files.
	 */
	public function test_default_logging_info()
	{
		if (function_exists('wc_get_logger')) {
			$this->markTestSkipped('WooCommerce active; file logging logic bypassed.');
		}

		$message = 'Info Message ' . rand(1000, 9999);
		$scope = 'unit-test-default';

		// Write using defaults (non-dated)
		tw_logger_info($message, $scope);

		// Verify the file exists without a date suffix
		$file = tw_logger_filename('info', $scope, 0);
		$this->assertFileExists($file);

		// Manual read required as the library reader enforces dated files
		$content = file_get_contents($file);
		$this->assertStringContainsString($message, $content);
	}

	/**
	 * Test dated logging explicitly.
	 */
	public function test_dated_logging_read_write()
	{
		if (function_exists('wc_get_logger')) {
			$this->markTestSkipped('WooCommerce active');
		}

		$message = 'Dated Message ' . rand(1000, 9999);
		$scope = 'unit-test-dated';
		$now = time();

		// Write with an explicit timestamp
		tw_logger_write($message, 'info', $scope, $now);

		// Verify the dated file exists
		$file = tw_logger_filename('info', $scope, $now);
		$this->assertFileExists($file);

		// Read back using the library function
		$logs = tw_logger_read('info', $scope, $now);
		$this->assertNotEmpty($logs);

		// Verify content, accounting for potential empty lines
		$last_entry = end($logs);
		if (empty($last_entry)) {
			$last_entry = prev($logs);
		}
		$this->assertStringContainsString($message, $last_entry);
	}

	/**
	 * Test the error wrapper function.
	 */
	public function test_logger_error_wrapper()
	{
		if (function_exists('wc_get_logger')) {
			$this->markTestSkipped('WooCommerce active');
		}

		$message = 'Critical Error Test';
		$scope = 'error-scope';

		// Trigger an error log
		tw_logger_error($message, $scope);

		// Verify the non-dated error file exists
		$file = tw_logger_filename('error', $scope, 0);
		$this->assertFileExists($file);

		// Validate the file content
		$content = file_get_contents($file);
		$this->assertStringContainsString($message, $content);
	}

}