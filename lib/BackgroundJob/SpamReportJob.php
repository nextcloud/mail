<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AntiSpamService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

/**
 * Sends the spam or ham reports of messages that were marked in bulk
 */
class SpamReportJob extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private AccountService $accountService,
		private MailboxMapper $mailboxMapper,
		private AntiSpamService $antiSpamService,
		private LoggerInterface $logger,
	) {
		parent::__construct($time);
	}

	/**
	 * @param array{accountId: int, mailboxId: int, uids: int[], flag: string} $argument
	 */
	#[\Override]
	protected function run($argument): void {
		try {
			$account = $this->accountService->findById($argument['accountId']);
			$mailbox = $this->mailboxMapper->findById($argument['mailboxId']);
		} catch (DoesNotExistException $e) {
			$this->logger->info('Account or mailbox of the spam reports does not exist anymore', ['exception' => $e]);
			return;
		}

		foreach ($argument['uids'] as $uid) {
			try {
				$this->antiSpamService->sendReportEmail($account, $mailbox, $uid, $argument['flag']);
			} catch (ServiceException|ClientException $e) {
				$this->logger->error("Could not send the spam report of message $uid", ['exception' => $e]);
			}
		}
	}
}
