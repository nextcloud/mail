<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Exception_NoSupportExtension;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\TrashMailboxNotSetException;
use OCA\Mail\Folder;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\SyncResult;
use OCA\Mail\Service\Quota;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use function array_map;
use function array_merge;
use function array_values;
use function in_array;
use function is_array;

class ImapMessageConnector implements IMessageConnector {
	public function __construct(
		private ImapToDbSynchronizer $synchronizer,
		private MessageMapper $messageMapper,
		private IMAPClientFactory $imapClientFactory,
		private MailboxMapper $mailboxMapper,
		private DatabaseMessageMapper $dbMessageMapper,
		private ThreadMapper $threadMapper,
		private FolderMapper $folderMapper,
		private IEventDispatcher $eventDispatcher,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function syncAccount(Account $account, bool $force = false): void {
		$this->synchronizer->syncAccount($account, $this->logger, $force);
	}

	#[\Override]
	public function syncMessages(Account $account, Mailbox $mailbox, bool $force = false): SyncResult {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$rebuildThreads = $this->synchronizer->sync(
				$account,
				$client,
				$mailbox,
				$this->logger,
				force: $force,
			);
		} finally {
			$client->logout();
		}

		return new SyncResult(
			state: $mailbox->getSyncChangedToken(),
			stats: [
				'rebuildThreads' => $rebuildThreads,
			],
		);
	}

	#[\Override]
	public function syncMailbox(Account $account, Mailbox $mailbox, LoggerInterface $logger, int $criteria, ?array $knownUids = null, bool $force = false): SyncResult {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$rebuildThreads = $this->synchronizer->sync(
				$account,
				$client,
				$mailbox,
				$logger,
				$criteria,
				$knownUids,
				$force,
			);
		} finally {
			$client->logout();
		}

		return new SyncResult(
			state: $mailbox->getSyncChangedToken(),
			stats: [
				'rebuildThreads' => $rebuildThreads,
			],
		);
	}

	#[\Override]
	public function clearCache(Account $account, Mailbox $mailbox): void {
		$this->synchronizer->clearCache($account, $mailbox);
	}

	#[\Override]
	public function repairSync(Account $account, Mailbox $mailbox, LoggerInterface $logger): void {
		$this->synchronizer->repairSync($account, $mailbox, $logger);
	}

	#[\Override]
	public function fetchMessage(Account $account, Mailbox $mailbox, int $uid, bool $loadBody = false): IMAPMessage {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->messageMapper->find(
				$client,
				$mailbox->getName(),
				$uid,
				$account->getUserId(),
				$loadBody,
			);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function fetchMessageRaw(Account $account, Mailbox $mailbox, int $uid): ?string {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->messageMapper->getFullText(
				$client,
				$mailbox->getName(),
				$uid,
				$account->getUserId(),
				false,
			);
		} finally {
			$client->logout();
		}
	}

	/**
	 * @return Attachment[]
	 */
	#[\Override]
	public function fetchAttachments(Account $account, Mailbox $mailbox, int $uid): array {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->messageMapper->getAttachments(
				$client,
				$mailbox->getName(),
				$uid,
				$account->getUserId(),
			);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function fetchAttachment(Account $account, Mailbox $mailbox, int $uid, string $attachmentId): Attachment {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->messageMapper->getAttachment(
				$client,
				$mailbox->getName(),
				$uid,
				$attachmentId,
				$account->getUserId(),
			);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function flagMessages(Account $account, string $flag, bool $value, Message ...$messages): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$imapFlags = $this->filterFlags($client, $flag, $mailbox->getName());
			foreach ($imapFlags as $imapFlag) {
				if ($imapFlag === '') {
					continue;
				}
				if ($value) {
					$this->messageMapper->addFlag($client, $mailbox, [$uid], $imapFlag);
				} else {
					$this->messageMapper->removeFlag($client, $mailbox, [$uid], $imapFlag);
				}
			}
		} finally {
			$client->logout();
		}

		$this->eventDispatcher->dispatchTyped(
			new MessageFlaggedEvent($account, $mailbox, $uid, $flag, $value),
		);
	}

	public function tagMessages(Account $account, Tag $tag, bool $value, Message ...$messages): void {

	}

	#[\Override]
	public function moveMessage(Account $account, string $sourceMailbox, int $uid, string $destMailbox): ?int {
		$client = $this->imapClientFactory->getClient($account);
		try {
			return $this->messageMapper->move($client, $sourceMailbox, $uid, $destMailbox);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): array {
		if ($srcAccount->getId() !== $dstAccount->getId()) {
			throw new ServiceException('It is not possible to move across accounts yet');
		}

		$mailAccount = $srcAccount->getMailAccount();
		$messageInTrash = $srcMailbox->getId() === $mailAccount->getTrashMailboxId();
		$messages = $this->threadMapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot(
			$mailAccount,
			$threadRootId,
			$messageInTrash,
		);

		$client = $this->imapClientFactory->getClient($srcAccount);
		try {
			$newUids = [];
			foreach ($messages as $message) {
				$this->logger->debug('move message', [
					'messageId' => $message['messageUid'],
					'srcMailboxId' => $srcMailbox->getId(),
					'dstMailboxId' => $dstMailbox->getId(),
				]);

				$newUid = $this->messageMapper->move(
					$client,
					$message['mailboxName'],
					$message['messageUid'],
					$dstMailbox->getName(),
				);

				if ($newUid !== null) {
					$newUids[] = $newUid;
				}
			}
		} finally {
			$client->logout();
		}

		return $newUids;
	}

	#[\Override]
	public function deleteMessage(Account $account, Mailbox $mailbox, int $uid): void {
		$this->eventDispatcher->dispatchTyped(
			new BeforeMessageDeletedEvent($account, $mailbox->getName(), $uid),
		);

		try {
			$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
			if ($trashMailboxId === null) {
				throw new TrashMailboxNotSetException();
			}
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('No trash folder', 0, $e);
		}

		$client = $this->imapClientFactory->getClient($account);
		try {
			if ($mailbox->getName() === $trashMailbox->getName()) {
				$this->messageMapper->expunge($client, $mailbox->getName(), $uid);
			} else {
				$this->messageMapper->move($client, $mailbox->getName(), $uid, $trashMailbox->getName());
			}
		} finally {
			$client->logout();
		}

		$this->eventDispatcher->dispatchTyped(
			new MessageDeletedEvent($account, $mailbox, $uid),
		);
	}

	#[\Override]
	public function deleteThread(Account $account, Mailbox $mailbox, string $threadRootId): void {
		$mailAccount = $account->getMailAccount();
		$messageInTrash = $mailbox->getId() === $mailAccount->getTrashMailboxId();
		$messages = $this->threadMapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot(
			$mailAccount,
			$threadRootId,
			$messageInTrash,
		);

		foreach ($messages as $message) {
			$this->logger->debug('deleting message', [
				'messageId' => $message['messageUid'],
				'mailboxId' => $mailbox->getId(),
			]);

			$this->deleteMessage(
				$account,
				$this->mailboxMapper->find($account, $message['mailboxName']),
				$message['messageUid'],
			);
		}
	}

	#[\Override]
	public function markAllRead(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->messageMapper->markAllRead($client, $mailbox->getName());
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function clearMailbox(Account $account, Mailbox $mailbox): void {
		$client = $this->imapClientFactory->getClient($account);
		$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
		$currentMailboxId = $mailbox->getId();
		try {
			if (($currentMailboxId !== $trashMailboxId) && $trashMailboxId !== null) {
				$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
				$client->copy($mailbox->getName(), $trashMailbox->getName(), [
					'move' => true,
				]);
			} else {
				$client->expunge($mailbox->getName(), [
					'delete' => true,
				]);
			}
			$this->dbMessageMapper->deleteAll($mailbox);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function getQuota(Account $account): ?Quota {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$quotas = array_map(
				static fn (Folder $mailbox) => $client->getQuotaRoot($mailbox->getMailbox()),
				$this->folderMapper->getFolders($account, $client),
			);
		} catch (Horde_Imap_Client_Exception_NoSupportExtension) {
			return null;
		} finally {
			$client->logout();
		}

		$storageQuotas = array_map(static fn (array $root) => $root['storage'] ?? [
			'usage' => 0,
			'limit' => 0,
		], array_merge(...array_values($quotas)));

		if ($storageQuotas === []) {
			return null;
		}

		$storage = array_merge(...array_values($storageQuotas));

		return new Quota(
			1024 * (int)($storage['usage'] ?? 0),
			1024 * (int)($storage['limit'] ?? 0),
		);
	}

	#[\Override]
	public function isPermflagsEnabled(Account $account, string $mailbox): bool {
		$client = $this->imapClientFactory->getClient($account);
		try {
			$capabilities = $client->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get message flag options from IMAP: ' . $e->getMessage(),
				$e->getCode(),
				$e,
			);
		} finally {
			$client->logout();
		}

		return is_array($capabilities)
			&& in_array('\\*', $capabilities['permflags'] ?? [], true);
	}

	private function isPermflagsEnabledWithClient(Horde_Imap_Client_Socket $client, string $mailbox): bool {
		try {
			$capabilities = $client->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get message flag options from IMAP: ' . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}
		return (is_array($capabilities) === true && array_key_exists('permflags', $capabilities) === true && in_array("\*", $capabilities['permflags'], true) === true);
	}

	/**
	 * @return string[]
	 */
	private function filterFlags($client, string $flag, string $mailbox): array {
		$systemFlags = [
			'seen' => [Horde_Imap_Client::FLAG_SEEN],
			'answered' => [Horde_Imap_Client::FLAG_ANSWERED],
			'flagged' => [Horde_Imap_Client::FLAG_FLAGGED],
			'deleted' => [Horde_Imap_Client::FLAG_DELETED],
			'draft' => [Horde_Imap_Client::FLAG_DRAFT],
			'recent' => [Horde_Imap_Client::FLAG_RECENT],
		];

		if (isset($systemFlags[$flag])) {
			return $systemFlags[$flag];
		}

		try {
			$capabilities = $client->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get message flag options from IMAP: ' . $e->getMessage(),
				$e->getCode(),
				$e,
			);
		}

		if (!isset($capabilities['permflags'])) {
			return [];
		}

		if (in_array('\\*', $capabilities['permflags'], true) || in_array($flag, $capabilities['permflags'], true)) {
			return [$flag];
		}

		return [];
	}
}
