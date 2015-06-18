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

class Logger implements ILogger {

	/** @var array */
	private $context;

	/** @var ILogger */
	private $logger;

	/**
	 * 
	 * @param string $appName
	 * @param ILogger $logger
	 */
	public function __construct($appName, ILogger $logger) {
		$this->context = [
			'app' => $appName,
		];
		$this->logger = $logger;
	}

	/**
	 * @inheritdoc
	 */
	public function emergency($message, array $context = array()) {
		$this->logger->emergency($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function alert($message, array $context = array()) {
		$this->logger->alert($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function critical($message, array $context = array()) {
		$this->logger->critical($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function error($message, array $context = array()) {
		$this->logger->error($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function warning($message, array $context = array()) {
		$this->logger->warning($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function notice($message, array $context = array()) {
		$this->logger->notice($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function info($message, array $context = array()) {
		$this->logger->info($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function debug($message, array $context = array()) {
		$this->logger->debug($message, $this->context);
	}

	/**
	 * @inheritdoc
	 */
	public function log($level, $message, array $context = array()) {
		$this->logger->log($level, $message, $context);
	}
}
