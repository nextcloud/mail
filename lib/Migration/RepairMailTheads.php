<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\MessageMapper;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;
use function method_exists;

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

	#[\Override]
	public function getName(): string {
		return 'Repair Broken Threads for all mail accounts';
	}

	#[\Override]
	public function run(IOutput $output): void {
		/**
		 * During the upgrade to v1.11.3 and later the old version of the
		 * mapper is loaded before this new repair step is performed. Hence even after
		 * the program code got replaced, the class doesn't have the new method. We
		 */
		if (!method_exists($this->mapper, 'resetInReplyTo')) {
			$output->warning('New Mail code hasn\'t been loaded yet, skipping message ID repair. Please run `occ maintenance:repair` after the upgrade.');
			return;
		}

		$count = $this->mapper->resetInReplyTo();
		$this->logger->info('Repairing Mail Threading, ' . $count . ' messages updated');
		$output->info(sprintf('Repaired threads, %s messages updated', $count));
	}
}
