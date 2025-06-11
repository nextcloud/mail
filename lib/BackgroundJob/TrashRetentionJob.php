<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\MessageRetentionMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class TrashRetentionJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private LoggerInterface $logger,
		private IMAPClientFactory $clientFactory,
		private MessageMapper $messageMapper,
		private MessageRetentionMapper $messageRetentionMapper,
		private MailAccountMapper $accountMapper,
		private MailboxMapper $mailboxMapper,
		private IMailManager $mailManager,
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
		$accounts = $this->accountMapper->getAllAccounts();
		foreach ($accounts as $account) {
			$account = new Account($account);

			$retentionDays = $account->getMailAccount()->getTrashRetentionDays();
			if ($retentionDays === null || $retentionDays <= 0) {
				continue;
			}

			$retentionSeconds = $retentionDays * 24 * 3600;

			try {
				$this->cleanTrash($account, $retentionSeconds);
			} catch (ServiceException|ClientException $e) {
				$this->logger->error('Could not clean trash mailbox', [
					'exception' => $e,
					'userId' => $account->getUserId(),
					'accountId' => $account->getId(),
					'trashMailboxId' => $account->getMailAccount()->getTrashMailboxId(),
				]);
			}
		}

	}

	/**
	 * @throws ClientException
	 * @throws ServiceException
	 */
	private function cleanTrash(Account $account, int $retentionSeconds): void {
		$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
		if ($trashMailboxId === null) {
			return;
		}

		try {
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			return;
		}

		$now = $this->time->getTime();
		$messages = $this->messageMapper->findMessagesKnownSinceBefore(
			$trashMailboxId,
			$now - $retentionSeconds,
		);

		if (count($messages) === 0) {
			return;
		}

		$client = $this->clientFactory->getClient($account);
		try {
			foreach ($messages as $message) {
				$this->mailManager->deleteMessageWithClient(
					$account,
					$trashMailbox,
					$message->getUid(),
					$client,
				);
				$this->messageRetentionMapper->deleteByMailboxIdAndUid(
					$message->getMailboxId(),
					$message->getUid(),
				);
			}
		} finally {
			$client->logout();
		}
	}
}
