<?php
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
use OCA\Mail\Migration\MigrateImportantFromImapAndDb;
use OCA\Mail\Service\MailManager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use Psr\Log\LoggerInterface;

class MigrateImportantJob extends QueuedJob {

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	/** @var MailManager */
	private $mailManager;

	/** @var MigrateImportantFromImapAndDb */
	private $migration;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MailboxMapper $mailboxMapper,
								MailAccountMapper $mailAccountMapper,
								MailManager $mailManager,
								MigrateImportantFromImapAndDb $migration,
								LoggerInterface $logger,
								ITimeFactory $timeFactory
								) {
		parent::__construct($timeFactory);
		$this->mailboxMapper = $mailboxMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->mailManager = $mailManager;
		$this->migration = $migration;
		$this->logger = $logger;
	}

	/**
	 * @param array $argument
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
		if ($this->mailManager->isPermflagsEnabled($account, $mailbox->getName()) === false) {
			$this->logger->debug('Permflags not enabled for <' . $accountId . '>');
			return;
		}

		try {
			$this->migration->migrateImportantOnImap($account, $mailbox);
		} catch (ServiceException $e) {
			$this->logger->debug('Could not flag messages on IMAP for mailbox <' . $mailboxId . '>.');
		}

		try {
			$this->migration->migrateImportantFromDb($account, $mailbox);
		} catch (ServiceException $e) {
			$this->logger->debug('Could not flag messages from DB on IMAP for mailbox <' . $mailboxId . '>.');
		}
	}
}
