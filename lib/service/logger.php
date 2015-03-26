<?php

/**
 * ownCloud - mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @copyright Christoph Wurst 2015
 */

namespace OCA\Mail\Service;

use OCP\ILogger;

class Logger {

	/**
	 *
	 * @var array
	 */
	private $context;

	/**
	 *
	 * @var ILogger
	 */
	private $logger;

	/**
	 * 
	 * @param string $AppName
	 * @param ILogger $logger
	 */
	public function __construct($AppName, ILogger $logger) {
		$this->context = [
			'app' => $AppName,
		];
		$this->logger = $logger;
	}

	/**
	 * 
	 * @param string $message
	 */
	public function emergency($message) {
		$this->logger->emergency($message, $this->context);
	}

	/**
	 * 
	 * @param string $message
	 */
	public function alert($message) {
		$this->logger->alert($message, $this->context);
	}

	/**
	 * 
	 * @param string $message
	 */
	public function critical($message) {
		$this->logger->critical($message, $this->context);
	}

	/**
	 * 
	 * @param string $message
	 */
	public function error($message) {
		$this->logger->error($message, $this->context);
	}

	/**
	 * 
	 * @param string $message
	 */
	public function warning($message) {
		$this->logger->warning($message, $this->context);
	}

	/**
	 * 
	 * @param string $message
	 */
	public function notice($message) {
		$this->logger->notice($message, $this->context);
	}

	/**
	 * 
	 * @param string $message
	 */
	public function info($message) {
		$this->logger->info($message, $this->context);
	}

	/**
	 * 
	 * @param string $message
	 */
	public function debug($message) {
		$this->logger->debug($message, $this->context);
	}

}
