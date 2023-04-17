<?php
/**
 * Log processing library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 4.0
 */

/**
 * Log a message
 *
 * @param string $message
 * @param string $scope
 */
function tw_logger_info($message, $scope = 'theme') {
	tw_logger_write($message, 'info', $scope);
}


/**
 * Log an error
 *
 * @param string $message
 * @param string $scope
 */
function tw_logger_error($message, $scope = 'theme') {
	tw_logger_write($message, 'error', $scope);
}


/**
 * Write a message to logs
 *
 * @param string   $message
 * @param string   $type
 * @param string   $scope
 * @param bool|int $time
 */
function tw_logger_write($message, $type = 'info', $scope = 'theme', $time = true) {

	if (is_array($message) or is_object($message)) {
		$message = 'Object: ' . serialize($message);
	} else {
		$message = date('H:i:s') . ' ' . $message;
	}

	$filename = tw_logger_filename($type, $scope, $time);

	$handler = fopen($filename, 'a');

	fwrite($handler, $message . PHP_EOL);

	fclose($handler);

}


/**
 * Read a message to logs
 *
 * @param string   $type
 * @param string   $scope
 * @param bool|int $time
 *
 * @return array|false|string[]
 */
function tw_logger_read($type = 'info', $scope = 'theme', $time = true) {

	$logs = [];

	$filename = tw_logger_filename($type, $scope, $time);

	if (file_exists($filename)) {
		$logs = explode("\n", file_get_contents($filename));
	}

	return $logs;

}


/**
 * Get a log file name with full path
 *
 * @param string   $type
 * @param string   $scope
 * @param bool|int $time
 *
 * @return string
 */
function tw_logger_filename($type = 'info', $scope = 'theme', $time = true) {

	$directory = wp_get_upload_dir();

	if (!empty($directory['basedir'])) {
		$folder = $directory['basedir'] . '/cache/logs/';
	} else {
		$folder = get_template_directory() . '/cache/logs/';
	}

	if (!is_dir($folder)) {
		wp_mkdir_p($folder);
	}

	$filename = $folder . 'twee_log_' . $scope;

	if ($time) {

		if (!is_numeric($time)) {
			$time = time();
		}

		$filename .= '_' . date('Y_m_d', $time);

	}

	if ($type) {
		$type = '_' . $type;
	} else {
		$type = '';
	}

	return $filename . $type . '.log';

}