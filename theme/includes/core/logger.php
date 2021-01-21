<?php
/**
 * Log processing library
 *
 * @author  Andrii Toniievych <toniyevych@gmail.com>
 * @package Twee
 * @version 3.0
 */

namespace Twee;

class Logger {

	const INFO = 'info';

	const ERROR = 'error';

	protected $filename;


	public function __construct($handler = 'theme') {

		$directory = wp_get_upload_dir();

		if (!empty($directory['basedir'])) {
			$folder = $directory['basedir'] . '/cache/logs/';
		} else {
			$folder = get_template_directory() . '/cache/logs/';
		}

		if (!is_dir($folder)) {
			wp_mkdir_p($folder);
		}

		$this->filename = $folder . 'twee_log_' . $handler;

	}


	/**
	 * Log the message
	 *
	 * @param string $message
	 * @param string $type
	 */
	public function log($message, $type = self::INFO) {

		if (is_array($message) or is_object($message)) {
			$message = 'Object: ' . serialize($message);
		} else {
			$message = date('H:i:s') . ' ' . $message;
		}

		$filename = $this->filename($type);

		$handler = fopen($filename, 'a');

		fwrite($handler, $message . PHP_EOL);

		fclose($handler);

	}


	/**
	 * Log a message
	 *
	 * @param string $message
	 */
	public function info($message) {
		$this->log($message, self::INFO);
	}


	/**
	 * Log an error
	 *
	 * @param string $message
	 */
	public function error($message) {
		$this->log($message, self::ERROR);
	}


	/**
	 * Get the log file path
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	protected function filename($type) {

		if ($type) {
			$type = '_' . $type;
		} else {
			$type = '';
		}

		return $this->filename . $type . '.log';

	}


	/**
	 * Read the logs from file
	 *
	 * @param string $type
	 *
	 * @return string[]
	 */
	public function read($type = self::INFO) {

		$logs = [];

		$filename = $this->filename($type);

		if (file_exists($filename)) {
			$logs = explode("\n", file_get_contents($filename));
		}

		return $logs;

	}


	/**
	 * Clean logs files
	 */
	public function clean() {

		$types = [self::INFO, self::ERROR];

		foreach ($types as $type) {

			$filename = $this->filename($type);

			if (file_exists($filename)) {
				unlink($filename);
			}

		}

	}

}