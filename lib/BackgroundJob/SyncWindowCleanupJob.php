<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class SyncWindowCleanupJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private LoggerInterface $logger,
		private MessageMapper $messageMapper,
		private MailAccountMapper $accountMapper,
		private MailboxMapper $mailboxMapper,
		private IConfig $config,
	) {
		parent::__construct($time);

		$this->setInterval(24 * 3600);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function run($argument) {
		$maxSyncDays = (int)$this->config->getAppValue('mail', 'max_sync_days', '0');
		if ($maxSyncDays <= 0) {
			return;
		}

		$cutoff = $this->time->getTime() - ($maxSyncDays * 86400);

		$accounts = $this->accountMapper->getAllAccounts();
		foreach ($accounts as $account) {
			$account = new Account($account);

			try {
				$this->cleanAccount($account, $cutoff);
			} catch (\Exception $e) {
				$this->logger->error('Could not clean old messages for sync window', [
					'exception' => $e,
					'userId' => $account->getUserId(),
					'accountId' => $account->getId(),
				]);
			}
		}
	}

	private function cleanAccount(Account $account, int $cutoff): void {
		$mailboxes = $this->mailboxMapper->findAll($account);

		foreach ($mailboxes as $mailbox) {
			$cleaned = 0;
			do {
				$messages = $this->messageMapper->findMessagesBefore(
					$mailbox->getId(),
					$cutoff,
				);

				if (count($messages) === 0) {
					break;
				}

				$uids = array_map(static fn ($message) => $message->getUid(), $messages);
				$this->messageMapper->deleteByUid($mailbox, ...$uids);
				$cleaned += count($uids);
			} while (true);

			if ($cleaned > 0) {
				$this->logger->debug('Cleaned {count} old messages from mailbox {mailboxId}', [
					'count' => $cleaned,
					'mailboxId' => $mailbox->getId(),
					'accountId' => $account->getId(),
				]);
			}
		}
	}
}
