<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna.larch@nextcloud.com>
 *
 * @author Anna Larch <anna.larch@nextcloud.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\BackgroundJob;

use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
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
	private MailboxMapper $mailboxMapper;
	private MailAccountMapper $mailAccountMapper;
	private MailManager $mailManager;
	private MigrateImportantFromImapAndDb $migration;
	private LoggerInterface $logger;
	private IMAPClientFactory $imapClientFactory;

	public function __construct(MailboxMapper $mailboxMapper,
								MailAccountMapper $mailAccountMapper,
								MailManager $mailManager,
								MigrateImportantFromImapAndDb $migration,
								LoggerInterface $logger,
								ITimeFactory $timeFactory,
								IMAPClientFactory $imapClientFactory
								) {
		parent::__construct($timeFactory);
		$this->mailboxMapper = $mailboxMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->mailManager = $mailManager;
		$this->migration = $migration;
		$this->logger = $logger;
		$this->imapClientFactory = $imapClientFactory;
	}

	/**
	 * @param array $argument
	 *
	 * @return void
	 */
	public function run($argument) {
		$mailboxId = (int)$argument['mailboxId'];
		try {
			/** @var Mailbox $mailbox*/
			$mailbox = $this->mailboxMapper->findById($mailboxId);
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Could not find mailbox <' . $mailboxId . '>');
			return;
		}

		$accountId = $mailbox->getAccountId();
		try {
			$mailAccount = $this->mailAccountMapper->findById($accountId);
		} catch (DoesNotExistException $e) {
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
			} catch (ServiceException $e) {
				$this->logger->debug('Could not flag messages on IMAP for mailbox <' . $mailboxId . '>.');
			}

			try {
				$this->migration->migrateImportantFromDb($client, $account, $mailbox);
			} catch (ServiceException $e) {
				$this->logger->debug('Could not flag messages from DB on IMAP for mailbox <' . $mailboxId . '>.');
			}
		} finally {
			$client->logout();
		}
	}
}
