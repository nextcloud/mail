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
use OCA\Mail\Protocol\ProtocolFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class ImapMailboxConnector implements IMailboxConnector {
	public function __construct(
		private readonly ProtocolFactory $protocolFactory,
		private readonly MailboxSync $mailboxSync,
		private readonly FolderMapper $folderMapper,
		private readonly MailboxMapper $mailboxMapper,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function syncAll(Account $account, bool $force = false): void {
		$this->mailboxSync->sync($account, $this->logger, $force);
	}

	#[\Override]
	public function syncOne(Account $account, Mailbox $mailbox): void {
		$client = $this->protocolFactory->imapClient($account);
		try {
			$this->mailboxSync->syncStats($client, $mailbox);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function create(Account $account, string $name, array $specialUse = []): Mailbox {
		$client = $this->protocolFactory->imapClient($account);
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
	public function rename(Account $account, Mailbox $mailbox, string $newName): Mailbox {
		$client = $this->protocolFactory->imapClient($account);
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
	public function delete(Account $account, Mailbox $mailbox): void {
		$client = $this->protocolFactory->imapClient($account);
		try {
			$this->folderMapper->delete($client, $mailbox->getName());
		} finally {
			$client->logout();
		}

		$this->mailboxMapper->delete($mailbox);
	}

	#[\Override]
	public function subscribe(Account $account, Mailbox $mailbox, bool $subscribed): Mailbox {
		$client = $this->protocolFactory->imapClient($account);
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
