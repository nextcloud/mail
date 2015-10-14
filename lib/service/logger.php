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
	public function emergency($message, array $context = []) {
		$this->logger->emergency($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function alert($message, array $context = []) {
		$this->logger->alert($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function critical($message, array $context = []) {
		$this->logger->critical($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function error($message, array $context = []) {
		$this->logger->error($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function warning($message, array $context = []) {
		$this->logger->warning($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function notice($message, array $context = []) {
		$this->logger->notice($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function info($message, array $context = []) {
		$this->logger->info($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function debug($message, array $context = []) {
		$this->logger->debug($message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function log($level, $message, array $context = array()) {
		$this->logger->log($level, $message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function logException(\Exception $exception, array $context = array()) {
		$this->logger->logException($exception, array_merge($this->context, $context));
	}

}
