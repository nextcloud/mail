<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Command;

use OCA\Mail\Service\CleanupService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUp extends Command {
	private CleanupService $cleanupService;

	public function __construct(CleanupService $cleanupService) {
		parent::__construct();

		$this->cleanupService = $cleanupService;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:clean-up');
		$this->setDescription('clean up all orphaned data');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$this->cleanupService->cleanUp();

		return 0;
	}
}
