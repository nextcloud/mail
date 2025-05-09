<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client;
use Horde_Imap_Client_Data_Namespace;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Namespace_List;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use function array_combine;
use function array_map;
use function array_reduce;
use function array_slice;
use function in_array;
use function json_encode;
use function shuffle;
use function sprintf;
use function str_starts_with;

class MailboxSync {
	use TTransactional;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var FolderMapper */
	private $folderMapper;

	/** @var MailAccountMapper */
	private $mailAccountMapper;

	/** @var IMAPClientFactory */
	private $imapClientFactory;

	/** @var ITimeFactory */
	private $timeFactory;

	/** @var IEventDispatcher */
	private $dispatcher;
	private IDBConnection $dbConnection;

	public function __construct(MailboxMapper $mailboxMapper,
		FolderMapper $folderMapper,
		MailAccountMapper $mailAccountMapper,
		IMAPClientFactory $imapClientFactory,
		ITimeFactory $timeFactory,
		IEventDispatcher $dispatcher,
		IDBConnection $dbConnection) {
		$this->mailboxMapper = $mailboxMapper;
		$this->folderMapper = $folderMapper;
		$this->mailAccountMapper = $mailAccountMapper;
		$this->imapClientFactory = $imapClientFactory;
		$this->timeFactory = $timeFactory;
		$this->dispatcher = $dispatcher;
		$this->dbConnection = $dbConnection;
	}

	/**
	 * @throws ServiceException
	 */
	public function sync(Account $account,
		LoggerInterface $logger,
		bool $force = false): void {
		if (!$force && $account->getMailAccount()->getLastMailboxSync() >= ($this->timeFactory->getTime() - 7200)) {
			$logger->debug('account is up to date, skipping mailbox sync');
			return;
		}

		$client = $this->imapClientFactory->getClient($account);
		try {
			try {
				$namespaces = $client->getNamespaces([], [
					'ob_return' => true,
				]);
				$personalNamespace = $this->getPersonalNamespace($namespaces);
				$account->getMailAccount()->setPersonalNamespace(
					$personalNamespace
				);
			} catch (Horde_Imap_Client_Exception $e) {
				$logger->debug('Getting namespaces for account ' . $account->getId() . ' failed: ' . $e->getMessage(), [
					'exception' => $e,
				]);
				$namespaces = null;
				$personalNamespace = null;
			}

			try {
				$folders = $this->folderMapper->getFolders($account, $client);
				$this->folderMapper->fetchFolderAcls($folders, $client);
			} catch (Horde_Imap_Client_Exception $e) {
				throw new ServiceException(
					sprintf('IMAP error synchronizing account %d: %s', $account->getId(), $e->getMessage()),
					$e->getCode(),
					$e
				);
			}
			$this->folderMapper->detectFolderSpecialUse($folders);

			$mailboxes = $this->atomic(function () use ($account, $folders, $namespaces) {
				$old = $this->mailboxMapper->findAll($account);
				$indexedOld = array_combine(
					array_map(static function (Mailbox $mb) {
						return $mb->getName();
					}, $old),
					$old
				);

				return $this->persist($account, $folders, $indexedOld, $namespaces);
			}, $this->dbConnection);

			$this->syncMailboxStatus($mailboxes, $personalNamespace, $client);

			$this->dispatcher->dispatchTyped(
				new MailboxesSynchronizedEvent($account)
			);
		} finally {
			$client->logout();
		}
	}

	/**
	 * Sync unread and total message statistics.
	 *
	 * @param Mailbox $mailbox
	 *
	 * @throws ServiceException
	 */
	public function syncStats(Horde_Imap_Client_Socket $client, Mailbox $mailbox): void {
		try {
			$allStats = $this->folderMapper->getFoldersStatusAsObject($client, [$mailbox->getName()]);
		} catch (Horde_Imap_Client_Exception $e) {
			$id = $mailbox->getId();
			throw new ServiceException(
				"Could not fetch stats of mailbox $id. IMAP error: " . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}

		if (!isset($allStats[$mailbox->getName()])) {
			return;
		}

		$stats = $allStats[$mailbox->getName()];
		$mailbox->setMessages($stats->getTotal());
		$mailbox->setUnseen($stats->getUnread());
		$this->mailboxMapper->update($mailbox);
	}

	/**
	 * @return Mailbox[]
	 */
	private function persist(Account $account,
		array $folders,
		array $existing,
		?Horde_Imap_Client_Namespace_List $namespaces): array {
		$mailboxes = [];
		foreach ($folders as $folder) {
			if (isset($existing[$folder->getMailbox()])) {
				$mailboxes[] = $this->updateMailboxFromFolder(
					$folder,
					$existing[$folder->getMailbox()],
					$namespaces,
				);
			} else {
				$mailboxes[] = $this->createMailboxFromFolder(
					$account,
					$folder,
					$namespaces,
				);
			}

			unset($existing[$folder->getMailbox()]);
		}

		foreach ($existing as $leftover) {
			$this->mailboxMapper->delete($leftover);
		}

		$account->getMailAccount()->setLastMailboxSync($this->timeFactory->getTime());
		$this->mailAccountMapper->update($account->getMailAccount());

		return $mailboxes;
	}

	private function getPersonalNamespace(Horde_Imap_Client_Namespace_List $namespaces): ?string {
		foreach ($namespaces as $namespace) {
			/** @var Horde_Imap_Client_Data_Namespace $namespace */
			if ($namespace->type === Horde_Imap_Client::NS_PERSONAL) {
				return $namespace->name !== '' ? $namespace->name : null;
			}
		}
		return null;
	}

	private function updateMailboxFromFolder(Folder $folder, Mailbox $mailbox, ?Horde_Imap_Client_Namespace_List $namespaces): Mailbox {
		$mailbox->setAttributes(json_encode($folder->getAttributes()));
		$mailbox->setDelimiter($folder->getDelimiter());
		$status = $folder->getStatus();
		if ($status !== null) {
			$mailbox->setMessages($status['messages'] ?? 0);
			$mailbox->setUnseen($status['unseen'] ?? 0);
		}
		$mailbox->setSelectable(!in_array('\noselect', $folder->getAttributes()));
		$mailbox->setSpecialUse(json_encode($folder->getSpecialUse()));
		$mailbox->setMyAcls($folder->getMyAcls());
		$mailbox->setShared($this->isMailboxShared($namespaces, $mailbox));
		return $this->mailboxMapper->update($mailbox);
	}

	private function createMailboxFromFolder(Account $account, Folder $folder, ?Horde_Imap_Client_Namespace_List $namespaces): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setName($folder->getMailbox());
		$mailbox->setAccountId($account->getId());
		$mailbox->setAttributes(json_encode($folder->getAttributes()));
		$mailbox->setDelimiter($folder->getDelimiter());
		$status = $folder->getStatus();
		$mailbox->setMessages($status['messages'] ?? 0);
		$mailbox->setUnseen($status['unseen'] ?? 0);
		$mailbox->setSelectable(!in_array('\noselect', $folder->getAttributes()));
		$mailbox->setSpecialUse(json_encode($folder->getSpecialUse()));
		$mailbox->setMyAcls($folder->getMyAcls());
		$mailbox->setShared($this->isMailboxShared($namespaces, $mailbox));
		$mailbox->setNameHash(md5($folder->getMailbox()));
		return $this->mailboxMapper->insert($mailbox);
	}

	private function isMailboxShared(?Horde_Imap_Client_Namespace_List $namespaces, Mailbox $mailbox): bool {
		foreach (($namespaces ?? []) as $namespace) {
			/** @var Horde_Imap_Client_Data_Namespace $namespace */
			if ($namespace->type === Horde_Imap_Client_Data_Namespace::NS_OTHER && str_starts_with($mailbox->getName(), $namespace->name)) {
				return true;
			}
		}
		return false;
	}

	private function syncMailboxStatus(mixed $mailboxes, ?string $personalNamespace, \Horde_Imap_Client_Socket $client): void {
		/** @var array{0: Mailbox[], 1: Mailbox[]} */
		[$sync, $doNotSync] = array_reduce($mailboxes, function (array $carry, Mailbox $mailbox) use ($personalNamespace): array {
			[$sync, $doNotSync] = $carry;
			$inboxName = $personalNamespace === null ? 'INBOX' : ($personalNamespace . $mailbox->getDelimiter() . 'INBOX');
			if ($inboxName === $mailbox->getName() || $mailbox->getSyncInBackground()) {
				return [
					array_merge($sync, [$mailbox]),
					$doNotSync,
				];
			} elseif ($mailbox->getSelectable() === false) {
				return [$sync, $doNotSync];
			}
			return [
				$sync,
				array_merge($doNotSync, [$mailbox]),
			];
		}, [[], []]);

		// Synchronize the mailboxes selected for sync and keep the rest updated occasionally
		shuffle($doNotSync);
		/** @var Mailbox[] $syncStatus */
		$syncStatus = [...$sync, ...array_slice($doNotSync, 0, 5)];
		$statuses = $this->folderMapper->getFoldersStatusAsObject($client, array_map(function (Mailbox $mailbox) {
			return $mailbox->getName();
		}, $syncStatus));
		foreach ($syncStatus as $mailbox) {
			$status = $statuses[$mailbox->getName()] ?? null;
			if ($status !== null) {
				$mailbox->setMessages($status->getTotal());
				$mailbox->setUnseen($status->getUnread());
			}
		}
		$this->atomic(function () use ($syncStatus) {
			foreach ($syncStatus as $mailbox) {
				$this->mailboxMapper->update($mailbox);
			}
		}, $this->dbConnection);
	}
}
