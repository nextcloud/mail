<?php

declare(strict_types=1);

/*
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author 2022 Anna Larch <anna.larch@gmx.net>
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

use OCA\Mail\Service\DraftsService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class DraftsJob extends TimedJob {
	private DraftsService $draftsService;

	public function __construct(ITimeFactory $time,
		DraftsService $draftsService) {
		parent::__construct($time);

		// Run once per five minutes
		$this->setInterval(5 * 60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
		$this->draftsService = $draftsService;
	}

	protected function run($argument): void {
		$this->draftsService->flush();
	}
}
