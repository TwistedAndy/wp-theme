<?php
/**
 * Log processing library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.2
 */

/**
 * Log a message
 *
 * @param string $message
 * @param string $scope
 */
function tw_logger_info(string $message, string $scope = 'theme'): void
{
	tw_logger_write($message, 'info', $scope);
}


/**
 * Log an error
 *
 * @param string $message
 * @param string $scope
 */
function tw_logger_error(string $message, string $scope = 'theme'): void
{
	tw_logger_write($message, 'error', $scope);
}


/**
 * Write a message to logs
 *
 * @param string $message
 * @param string $type
 * @param string $scope
 * @param int    $time
 */
function tw_logger_write(string $message, string $type = 'info', string $scope = 'theme', int $time = 0): void
{
	if (function_exists('wc_get_logger')) {
		$logger = wc_get_logger();
		$context = ['source' => $scope];

		if ('error' === $type) {
			$logger->error($message, $context);
		} elseif ('info' === $type) {
			$logger->info($message, $context);
		} elseif ('debug' === $type) {
			$logger->debug($message, $context);
		} else {
			$logger->notice($message, $context);
		}
	} else {
		$message = date('H:i:s') . ' ' . $message;
		$filename = tw_logger_filename($type, $scope, $time);

		$dir = dirname($filename);
		if (!is_dir($dir)) {
			wp_mkdir_p($dir);
		}

		$handler = fopen($filename, 'a');
		if ($handler) {
			fwrite($handler, $message . PHP_EOL);
			fclose($handler);
		}
	}
}


/**
 * Read a message from logs
 *
 * @param string $type
 * @param string $scope
 * @param int    $time
 *
 * @return array
 */
function tw_logger_read(string $type = 'info', string $scope = 'theme', int $time = 0): array
{
	if ($time < 1) {
		$time = time();
	}

	$logs = [];
	$filename = tw_logger_filename($type, $scope, $time);

	if (file_exists($filename)) {
		$content = file_get_contents($filename);
		if ($content) {
			$logs = explode("\n", $content);
		}
	}

	return $logs;
}


/**
 * Get a log file name with full path
 *
 * @param string $type
 * @param string $scope
 * @param int    $time
 *
 * @return string
 */
function tw_logger_filename(string $type = 'info', string $scope = 'theme', int $time = 0): string
{
	$directory = wp_get_upload_dir();

	if (!empty($directory['basedir'])) {
		$folder = $directory['basedir'] . '/cache/logs/';
	} else {
		$folder = get_template_directory() . '/cache/logs/';
	}

	$filename = $folder . 'twee_log_' . $scope;

	if ($time > 0) {
		$filename .= '_' . date('Y_m_d', (int) $time);
	}

	if ($type) {
		$type = '_' . $type;
	} else {
		$type = '';
	}

	return $filename . $type . '.log';
}