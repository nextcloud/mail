<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\MessageMapper;
use Psr\Log\LoggerInterface;

class MigrateImportantFromImapAndDb {


	/** @var MessageMapper */
	private $messageMapper;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(MessageMapper $messageMapper,
		MailboxMapper $mailboxMapper,
		LoggerInterface $logger,
	) {
		$this->messageMapper = $messageMapper;
		$this->mailboxMapper = $mailboxMapper;
		$this->logger = $logger;
	}

	public function migrateImportantOnImap(Horde_Imap_Client_Socket $client, Account $account, Mailbox $mailbox): void {
		try {
			$uids = $this->messageMapper->getFlagged($client, $mailbox, '$important');
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not fetch UIDs of important messages: ' . $e->getMessage(), 0, $e);
		}
		// add $label1 for all that are tagged on IMAP
		if ($uids !== []) {
			try {
				$this->messageMapper->addFlag($client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
			} catch (Horde_Imap_Client_Exception $e) {
				$this->logger->debug('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
				throw new ServiceException($e->getMessage(), 0, $e);
			}
		}
	}

	public function migrateImportantFromDb(Horde_Imap_Client_Socket $client, Account $account, Mailbox $mailbox): void {
		$uids = $this->mailboxMapper->findFlaggedImportantUids($mailbox->getId());
		// store our data on imap
		if ($uids !== []) {
			try {
				$this->messageMapper->addFlag($client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
			} catch (Horde_Imap_Client_Exception $e) {
				$this->logger->debug('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
				throw new ServiceException($e->getMessage(), 0, $e);
			}
		}
	}
}
