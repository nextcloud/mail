<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;
use function function_exists;
use function memory_get_peak_usage;
use function memory_get_usage;
use function round;
use function sprintf;

class PerformanceLoggerTask {
	/** @var string */
	private $task;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var LoggerInterface */
	private $logger;

	/** @var int */
	private $start;

	/** @var int */
	private $rel;

	public function __construct(string $task,
								ITimeFactory $timeFactory,
								LoggerInterface $logger) {
		$this->task = $task;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;

		$this->start = $this->rel = $timeFactory->getTime();
	}

	public function step(string $description): void {
		$now = $this->timeFactory->getTime();
		$passed = $now - $this->rel;

		$message = $this->task . " - $description took {$passed}s.";
		if (function_exists('memory_get_usage') && function_exists('memory_get_peak_usage')) {
			$this->logger->debug(
				sprintf(
					$message . " %d/%dMB memory used",
					round(memory_get_usage() / 1024 / 1024),
					round(memory_get_peak_usage() / 1024 / 1024)
				)
			);
		} else {
			$this->logger->debug($message);
		}

		$this->rel = $now;
	}

	public function end(): int {
		$now = $this->timeFactory->getTime();
		$passed = $now - $this->start;

		$this->logger->debug($this->task . " took {$passed}s");

		return $passed;
	}
}
