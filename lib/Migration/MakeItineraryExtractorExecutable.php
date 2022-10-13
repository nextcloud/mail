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

namespace OCA\Mail\Migration;

use Exception;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use Throwable;
use function chmod;
use function is_executable;
use function is_file;

class MakeItineraryExtractorExecutable implements IRepairStep {
	/** @var LoggerInterface */
	private $logger;

	/** @var string */
	private $file;

	public function __construct(LoggerInterface $logger,
								string $file = null) {
		$this->file = $file ?? __DIR__ . '/../../vendor/christophwurst/kitinerary-bin/bin/kitinerary-extractor';
		$this->logger = $logger;
	}

	public function getName() {
		return 'Make Mail itinerary extractor executable';
	}

	/**
	 * @return void
	 */
	public function run(IOutput $output) {
		if (!is_file($this->file)) {
			$this->logger->warning('itinerary file doesn\'t exist');
			$output->info('itinerary file doesn\'t exist');
			return;
		}
		if (is_executable($this->file)) {
			$this->logger->debug('itinerary is already executable');
			return;
		}
		try {
			if (!@chmod($this->file, 0744)) {
				throw new Exception('chmod returned false');
			}
		} catch (Throwable $e) {
			$this->logger->error('Can\'t make itinerary extractor executable: ' . $e, [
				'exception' => $e,
			]);
			$output->warning('Can\'t make itinerary extractor executable: ' . $e->getMessage());
			return;
		}
	}
}
