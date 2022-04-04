<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna@nextcloud.com>
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
 *
 * @link https://github.com/nextcloud/mail/issues/25
 * @link https://github.com/nextcloud/mail/issues/4780
 */

namespace OCA\Mail\Migration;

use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use Psr\Log\LoggerInterface;

class MigrateImportantFromImapAndDb {

	/** @var IMAPClientFactory */
	private $clientFactory;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(IMAPClientFactory $clientFactory,
								MessageMapper $messageMapper,
								MailboxMapper $mailboxMapper,
								LoggerInterface $logger
								) {
		$this->clientFactory = $clientFactory;
		$this->messageMapper = $messageMapper;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
	}

	public function migrateImportantOnImap(Account $account, Mailbox $mailbox): void {
		$client = $this->clientFactory->getClient($account);
		try {
			//get all messages that have an $important label from IMAP
			try {
				$uids = $this->messageMapper->getFlagged($client, $mailbox, '$important');
			} catch (Horde_Imap_Client_Exception $e) {
				throw new ServiceException("Could not fetch UIDs of important messages: " . $e->getMessage(), 0, $e);
			}
			// add $label1 for all that are tagged on IMAP
			if (!empty($uids)) {
				try {
					$this->messageMapper->addFlag($client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
				} catch (Horde_Imap_Client_Exception $e) {
					$this->logger->debug('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
					throw new ServiceException($e->getMessage(), 0, $e);
				}
			}
		} finally {
			$client->logout();
		}
	}

	public function migrateImportantFromDb(Account $account, Mailbox $mailbox): void {
		$client = $this->clientFactory->getClient($account);
		try {
			$uids = $this->mailboxMapper->findFlaggedImportantUids($mailbox->getId());
			// store our data on imap
			if (!empty($uids)) {
				try {
					$this->messageMapper->addFlag($client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
				} catch (Horde_Imap_Client_Exception $e) {
					$this->logger->debug('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
					throw new ServiceException($e->getMessage(), 0, $e);
				}
			}
		} finally {
			$client->logout();
		}
	}
}
