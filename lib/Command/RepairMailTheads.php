<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@nextcloud.com>
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

namespace OCA\Mail\Command;

use OCA\Mail\Db\MessageMapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RepairMailTheads extends Command {

	/** @var MessageMapper */
	private $mapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MessageMapper $mapper,
								LoggerInterface $logger) {
		parent::__construct();

		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('mail:repair:threads');
		$this->setDescription('Repair Broken Threads for all mail accounts');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$count = $this->mapper->resetInReplyTo();
		$this->logger->info('Repairing Mail Threading, ' . $count . ' messages updated');
		$output->writeln('');
		$output->writeln('Repaired threads, ' . $count . ' messages updated');
		return 0;
	}
}
