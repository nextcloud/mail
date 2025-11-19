<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\MailboxMapper;

use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Migration\MigrateImportantFromImapAndDb;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

class MigrateImportantJob extends QueuedJob {
	public function __construct(
		private readonly MailboxMapper $mailboxMapper,
		private readonly MailAccountMapper $mailAccountMapper,
		private readonly MailManager $mailManager,
		private readonly MigrateImportantFromImapAndDb $migration,
		private readonly LoggerInterface $logger,
		ITimeFactory $timeFactory,
		private readonly IMAPClientFactory $imapClientFactory,
	) {
		parent::__construct($timeFactory);
	}

	/**
	 * @param array $argument
	 */
	#[\Override]
	public function run($argument): void {
		$mailboxId = (int)$argument['mailboxId'];
		try {
			$mailbox = $this->mailboxMapper->findById($mailboxId);
		} catch (DoesNotExistException) {
			$this->logger->debug('Could not find mailbox <' . $mailboxId . '>');
			return;
		}

		$accountId = $mailbox->getAccountId();
		try {
			$mailAccount = $this->mailAccountMapper->findById($accountId);
		} catch (DoesNotExistException) {
			$this->logger->debug('Could not find account <' . $accountId . '>');
			return;
		}

		$account = new Account($mailAccount);
		$client = $this->imapClientFactory->getClient($account);

		try {
			if ($this->mailManager->isPermflagsEnabled($client, $account, $mailbox->getName()) === false) {
				$this->logger->debug('Permflags not enabled for <' . $accountId . '>');
				return;
			}

			try {
				$this->migration->migrateImportantOnImap($client, $account, $mailbox);
			} catch (ServiceException) {
				$this->logger->debug('Could not flag messages on IMAP for mailbox <' . $mailboxId . '>.');
			}

			try {
				$this->migration->migrateImportantFromDb($client, $account, $mailbox);
			} catch (ServiceException) {
				$this->logger->debug('Could not flag messages from DB on IMAP for mailbox <' . $mailboxId . '>.');
			}
		} finally {
			$client->logout();
		}
	}
}
