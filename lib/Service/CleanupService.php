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

namespace OCA\Mail\Service;

use OCA\Mail\Db\AliasMapper;
use OCA\Mail\Db\CollectedAddressMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\MessageRetentionMapper;
use OCA\Mail\Db\MessageSnoozeMapper;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Service\Classification\PersistenceService;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;

class CleanupService {
	/** @var AliasMapper */
	private $aliasMapper;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var CollectedAddressMapper */
	private $collectedAddressMapper;

	/** @var TagMapper */
	private $tagMapper;

	private MessageRetentionMapper $messageRetentionMapper;

	private MessageSnoozeMapper $messageSnoozeMapper;

	private PersistenceService $classifierPersistenceService;
	private ITimeFactory $timeFactory;

	public function __construct(AliasMapper $aliasMapper,
		MailboxMapper $mailboxMapper,
		MessageMapper $messageMapper,
		CollectedAddressMapper $collectedAddressMapper,
		TagMapper $tagMapper,
		MessageRetentionMapper $messageRetentionMapper,
		MessageSnoozeMapper $messageSnoozeMapper,
		PersistenceService $classifierPersistenceService,
		ITimeFactory $timeFactory) {
		$this->aliasMapper = $aliasMapper;
		$this->mailboxMapper = $mailboxMapper;
		$this->messageMapper = $messageMapper;
		$this->collectedAddressMapper = $collectedAddressMapper;
		$this->tagMapper = $tagMapper;
		$this->messageRetentionMapper = $messageRetentionMapper;
		$this->messageSnoozeMapper = $messageSnoozeMapper;
		$this->classifierPersistenceService = $classifierPersistenceService;
		$this->timeFactory = $timeFactory;
	}

	public function cleanUp(LoggerInterface $logger): void {
		$task = (new PerformanceLogger(
			$this->timeFactory,
			$logger
		))->start('clean up');
		$this->aliasMapper->deleteOrphans();
		$task->step('delete orphan aliases');
		$this->mailboxMapper->deleteOrphans();
		$task->step('delete orphan mailboxes');
		$this->messageMapper->deleteOrphans();
		$task->step('delete orphan messages');
		$this->collectedAddressMapper->deleteOrphans();
		$task->step('delete orphan collected addresses');
		$this->tagMapper->deleteOrphans();
		$task->step('delete orphan tags');
		$this->tagMapper->deleteDuplicates();
		$task->step('delete duplicate tags');
		$this->messageRetentionMapper->deleteOrphans();
		$task->step('delete expired messages');
		$this->messageSnoozeMapper->deleteOrphans();
		$task->step('delete orphan snoozes');
		$this->classifierPersistenceService->cleanUp();
		$task->step('delete orphan classifiers');
		$task->end();
	}
}
