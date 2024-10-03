<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Support;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleLoggerDecorator implements LoggerInterface {
	/** @var LoggerInterface */
	private $inner;

	/** @var OutputInterface */
	private $consoleOutput;

	public function __construct(LoggerInterface $inner,
		OutputInterface $consoleOutput) {
		$this->inner = $inner;
		$this->consoleOutput = $consoleOutput;
	}

	public function emergency($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[emergency] $message</error>");

		$this->inner->emergency($message, $context);
	}

	public function alert($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[alert] $message</error>");

		$this->inner->alert($message, $context);
	}

	public function critical($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[critical] $message</error>");

		$this->inner->critical($message, $context);
	}

	public function error($message, array $context = []): void {
		$this->consoleOutput->writeln("<error>[error] $message</error>");

		$this->inner->error($message, $context);
	}

	public function warning($message, array $context = []): void {
		$this->consoleOutput->writeln("[warning] $message");

		$this->inner->warning($message, $context);
	}

	public function notice($message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[notice] $message</info>");

		$this->inner->notice($message, $context);
	}

	public function info($message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[info] $message</info>");

		$this->inner->info($message, $context);
	}

	public function debug($message, array $context = []): void {
		if ($this->consoleOutput->getVerbosity() < OutputInterface::VERBOSITY_DEBUG) {
			return;
		}

		$this->consoleOutput->writeln("[debug] $message");

		$this->inner->debug($message, $context);
	}

	public function log($level, $message, array $context = []): void {
		$this->consoleOutput->writeln("<info>[log] $message</info>");

		$this->inner->log($level, $message, $context);
	}
}
