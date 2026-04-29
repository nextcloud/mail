<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Exception;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class ImapMailboxConnector implements IMailboxConnector {
	public function __construct(
		private MailboxSync $mailboxSync,
		private FolderMapper $folderMapper,
		private IMAPClientFactory $imapClientFactory,
		private MailboxMapper $mailboxMapper,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function syncAccount(Account $account, bool $force = false): void {
		$this->mailboxSync->sync($account, $this->logger, $force);
	}

	#[\Override]
	public function syncMailbox(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->mailboxSync->syncStats($client, $mailbox);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function createMailbox(Account $account, string $name, array $specialUse = []): Mailbox {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$folder = $this->folderMapper->createFolder($client, $name, $specialUse);
			$this->folderMapper->fetchFolderAcls([$folder], $client);
			$this->folderMapper->detectFolderSpecialUse([$folder]);
			$this->mailboxSync->sync($account, $this->logger, true, $client);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get mailbox status: ' . $e->getMessage(),
				$e->getCode(),
				$e,
			);
		} finally {
			$client->logout();
		}

		return $this->mailboxMapper->find($account, $name);
	}

	#[\Override]
	public function renameMailbox(Account $account, Mailbox $mailbox, string $newName): Mailbox {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->folderMapper->renameFolder($client, $mailbox->getName(), $newName);
			$this->mailboxSync->sync($account, $this->logger, true, $client);
		} finally {
			$client->logout();
		}

		try {
			return $this->mailboxMapper->find($account, $newName);
		} catch (DoesNotExistException $e) {
			throw new ServiceException("The renamed mailbox $newName does not exist", 0, $e);
		}
	}

	#[\Override]
	public function deleteMailbox(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->folderMapper->delete($client, $mailbox->getName());
		} finally {
			$client->logout();
		}

		$this->mailboxMapper->delete($mailbox);
	}

	#[\Override]
	public function subscribeMailbox(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$client->subscribeMailbox($mailbox->getName(), $subscribed);
			$this->mailboxSync->sync($account, $this->logger, true, $client);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not set subscription status for mailbox ' . $mailbox->getId() . ' on IMAP: ' . $e->getMessage(),
				$e->getCode(),
				$e,
			);
		} finally {
			$client->logout();
		}

		return $this->mailboxMapper->find($account, $mailbox->getName());
	}
}
