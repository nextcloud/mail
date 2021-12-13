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
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class RepairMailTheads implements IRepairStep {

	/** @var MessageMapper */
	private $mapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MessageMapper $mapper,
								LoggerInterface $logger) {
		$this->mapper = $mapper;
		$this->logger = $logger;
	}

	public function getName(): string {
		return 'Repair Broken Threads for all mail accounts';
	}

	public function run(IOutput $output): void {
		$count = $this->mapper->resetInReplyTo();
		$this->logger->info('Repairing Mail Threading, ' . $count . ' messages updated');
		$output->info(sprintf('Repaired threads, %s messages updated', $count));
	}
}
