<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use OCP\ILogger;

class Logger {

	/** @var array */
	private $context;

	/** @var ILogger */
	private $logger;

	public function __construct(string $appName, ILogger $logger) {
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
	public function log($level, $message, array $context = []) {
		$this->logger->log($level, $message, array_merge($this->context, $context));
	}

	/**
	 * @inheritdoc
	 */
	public function logException($exception, array $context = []) {
		$this->logger->logException($exception, array_merge($this->context, $context));
	}

}
