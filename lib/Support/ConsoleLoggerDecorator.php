<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Support;

use OCP\ILogger;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ConsoleLoggerDecorator implements ILogger {

	/** @var ILogger */
	private $inner;

	/** @var OutputInterface */
	private $consoleOutput;

	public function __construct(ILogger $inner,
								OutputInterface $consoleOutput) {
		$this->inner = $inner;
		$this->consoleOutput = $consoleOutput;
	}

	public function emergency(string $message, array $context = []) {
		$this->consoleOutput->writeln("<error>[emergency] $message</error>");

		return $this->inner->emergency($message, $context);
	}

	public function alert(string $message, array $context = []) {
		$this->consoleOutput->writeln("<error>[alert] $message</error>");

		return $this->inner->alert($message, $context);
	}

	public function critical(string $message, array $context = []) {
		$this->consoleOutput->writeln("<error>[critical] $message</error>");

		return $this->inner->critical($message, $context);
	}

	public function error(string $message, array $context = []) {
		$this->consoleOutput->writeln("<error>[error] $message</error>");

		return $this->inner->error($message, $context);
	}

	public function warning(string $message, array $context = []) {
		$this->consoleOutput->writeln("[warning] $message");

		return $this->inner->warning($message, $context);
	}

	public function notice(string $message, array $context = []) {
		$this->consoleOutput->writeln("<info>[notice] $message</info>");

		return $this->inner->notice($message, $context);
	}

	public function info(string $message, array $context = []) {
		$this->consoleOutput->writeln("<info>[info] $message</info>");

		return $this->inner->info($message, $context);
	}

	public function debug(string $message, array $context = []) {
		$this->consoleOutput->writeln("[debug] $message");

		return $this->inner->debug($message, $context);
	}

	public function log(int $level, string $message, array $context = []) {
		$this->consoleOutput->writeln("<info>[log] $message</info>");

		return $this->inner->log($level, $message, $context);
	}

	public function logException(Throwable $exception, array $context = []) {
		$this->consoleOutput->writeln("<error>[exception] {$exception->getMessage()}</error>");

		$this->inner->logException($exception, $context);
	}
}
