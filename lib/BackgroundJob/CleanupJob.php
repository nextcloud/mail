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

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Service\CleanupService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use function defined;
use function method_exists;

class CleanupJob extends TimedJob {
	private CleanupService $cleanupService;

	public function __construct(ITimeFactory $time,
								CleanupService $cleanupService) {
		parent::__construct($time);
		$this->cleanupService = $cleanupService;

		$this->setInterval(24 * 60 * 60);
		/**
		 * @todo remove checks with 24+
		 */
		if (defined('\OCP\BackgroundJob\IJob::TIME_INSENSITIVE') && method_exists($this, 'setTimeSensitivity')) {
			$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		}
	}

	protected function run($argument): void {
		$this->cleanupService->cleanUp();
	}
}
