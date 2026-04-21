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
use Horde_Imap_Client_Search_Query;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper as DatabaseMailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Folder;
use OCA\Mail\IMAP\FolderMapper as ImapMailboxMapper;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Protocol\SyncResult;
use OCA\Mail\Service\Quota;
use OCA\Mail\Service\Search\SearchQuery;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;
use function array_map;
use function array_merge;
use function array_values;
use function in_array;
use function is_array;

class ImapMessageConnector implements IMessageConnector {
	public function __construct(
		private readonly DatabaseMailboxMapper $dbMailboxMapper,
		private readonly DatabaseMessageMapper $dbMessageMapper,
		private readonly ProtocolFactory $protocolFactory,
		private readonly ImapToDbSynchronizer $synchronizer,
		private readonly ImapMailboxMapper $imapMailboxMapper,
		private readonly ImapMessageMapper $imapMessageMapper,
		private readonly ThreadMapper $threadMapper,
		private readonly TagMapper $tagMapper,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function syncAll(Account $account, bool $force = false): void {
		$this->synchronizer->syncAccount($account, $this->logger, $force);
	}

	#[\Override]
	public function syncMailbox(Account $account, Mailbox $mailbox, LoggerInterface $logger, int $criteria, ?array $knownUids = null, bool $force = false): SyncResult {
		$client = $this->protocolFactory->imapClient($account);
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
	public function fetchMessages(Account $account, Mailbox $mailbox, bool $loadBody = false, Message ...$messages): array {
		$client = $this->protocolFactory->imapClient($account);
		$uids = array_map(static fn ($message) => $message->getUid(), $messages);
		try {
			return $this->imapMessageMapper->findByIds(
				$client,
				$mailbox->getName(),
				$uids,
				$account->getUserId(),
				true
			);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function findMessages(Account $account, Mailbox $mailbox, SearchQuery $searchQuery): array {
		$client = $this->protocolFactory->imapClient($account);
		try {
			$fetchResult = $client->search(
				$mailbox->getName(),
				$this->convertMailQueryToHordeQuery($searchQuery),
			);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not get message IDs: ' . $e->getMessage(), 0, $e);
		} finally {
			$client->logout();
		}

		return $fetchResult['match']->ids;
	}

	#[\Override]
	public function fetchMessageRaw(Account $account, Mailbox $mailbox, Message $message): ?string {
		$client = $this->protocolFactory->imapClient($account);
		try {
			return $this->imapMessageMapper->getFullText(
				$client,
				$mailbox->getName(),
				$message->getUid(),
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
	public function fetchAttachments(Account $account, Mailbox $mailbox, Message $message): array {
		$client = $this->protocolFactory->imapClient($account);
		try {
			return $this->imapMessageMapper->getAttachments(
				$client,
				$mailbox->getName(),
				$message->getUid(),
				$account->getUserId(),
			);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function fetchAttachment(Account $account, Mailbox $mailbox, Message $message, string $attachmentId): Attachment {
		$client = $this->protocolFactory->imapClient($account);
		try {
			return $this->imapMessageMapper->getAttachment(
				$client,
				$mailbox->getName(),
				$message->getUid(),
				$attachmentId,
				$account->getUserId(),
			);
		} finally {
			$client->logout();
		}
	}

	#[\Override]
	public function moveMessages(Account $account, Mailbox $targetMailbox, Mailbox $sourceMailbox, Message ...$messages): array {
		if ($messages === []) {
			return [];
		}
		$client = $this->protocolFactory->imapClient($account);

		$mutatedMessages = [];
		foreach ($messages as $message) {
			try {
				$newUid = $this->imapMessageMapper->move($client, $sourceMailbox->getName(), $message->getUid(), $targetMailbox->getName());
				$message->setUid($newUid);
				$message->setMailboxId($targetMailbox->getId());
				$mutatedMessages[] = $message;
			} catch (Horde_Imap_Client_Exception $e) {
				$this->logger->error('Could not move message on remote IMAP server', [
					'exception' => $e,
					'userId' => $account->getUserId(),
					'accountId' => $account->getId(),
					'sourceMailboxId' => $sourceMailbox->getId(),
					'targetMailboxId' => $targetMailbox->getId(),
					'messageUid' => $message->getUid(),
				]);
			}
		}

		$client->logout();

		return $mutatedMessages;
	}

	#[\Override]
	public function deleteMessages(Account $account, Mailbox $mailbox, Message ...$messages): array {
		if ($messages === []) {
			return [];
		}
		$client = $this->protocolFactory->imapClient($account);

		$mutatedMessages = [];
		foreach ($messages as $message) {
			try {
				$this->imapMessageMapper->expunge($client, $mailbox->getName(), $message->getUid());
				$mutatedMessages[] = $message;
			} catch (Horde_Imap_Client_Exception $e) {
				$this->logger->error('Could not delete message on remote IMAP server', [
					'exception' => $e,
					'userId' => $account->getUserId(),
					'accountId' => $account->getId(),
					'mailboxId' => $mailbox->getId(),
					'messageUid' => $message->getUid(),
				]);
			}
		}

		$client->logout();

		return $mutatedMessages;
	}

	#[\Override]
	public function flagMessages(Account $account, Mailbox $mailbox, string $flag, bool $value, Message ...$messages): array {
		if ($messages === []) {
			return [];
		}
		$client = $this->protocolFactory->imapClient($account);

		$uids = array_map(static fn (Message $message): int => $message->getUid(), $messages);
		try {
			$imapFlags = $this->filterFlags($client, $flag, $mailbox->getName());
			foreach ($imapFlags as $imapFlag) {
				if ($imapFlag === '') {
					continue;
				}
				// modify remote messages
				if ($value) {
					$this->imapMessageMapper->addFlag($client, $mailbox, $uids, $imapFlag);
				} else {
					$this->imapMessageMapper->removeFlag($client, $mailbox, $uids, $imapFlag);
				}
				// update local messages
				foreach ($messages as $message) {
					$message->setFlag($flag, $value);
				}
			}
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not set message flag on remote IMAP server: ' . $e->getMessage(), $e->getCode(), $e);
		}

		$client->logout();

		return $messages;
	}

	#[\Override]
	public function tagMessages(Account $account, Mailbox $mailbox, Tag $tag, bool $value, Message ...$messages): array {
		if ($messages === []) {
			return [];
		}
		$client = $this->protocolFactory->imapClient($account);

		if ($this->isPermflagsEnabledWithClient($client, $mailbox->getName()) === false) {
			$this->logger->error('Cannot set message keyword, server does not support permanent flags', ['tag' => $tag->getName()]);
			return [];
		}

		$uids = array_map(static fn (Message $message) => $message->getUid(), $messages);
		try {
			if ($value) {
				$this->imapMessageMapper->addFlag($client, $mailbox, $uids, $tag->getImapLabel());
			} else {
				$this->imapMessageMapper->removeFlag($client, $mailbox, $uids, $tag->getImapLabel());
			}
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException('Could not set message keyword on remote IMAP server: ' . $e->getMessage(), $e->getCode(), $e);
		}

		foreach ($messages as $message) {
			$this->applyTagValue($message, $tag, $value);
		}

		return $messages;
	}
	
	#[\Override]
	public function getQuota(Account $account): ?Quota {
		$client = $this->protocolFactory->imapClient($account);
		try {
			$quotas = array_map(
				static fn (Folder $mailbox) => $client->getQuotaRoot($mailbox->getMailbox()),
				$this->imapMailboxMapper->getFolders($account, $client),
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
	public function clearCache(Account $account, Mailbox $mailbox): void {
		$this->synchronizer->clearCache($account, $mailbox);
	}

	#[\Override]
	public function repairSync(Account $account, Mailbox $mailbox): void {
		$this->synchronizer->repairSync($account, $mailbox, $this->logger);
	}

	#[\Override]
	public function isPermflagsEnabled(Account $account, Mailbox $mailbox): bool {
		$client = $this->protocolFactory->imapClient($account);
		return $this->isPermflagsEnabledWithClient($client, $mailbox->getName());
	}

	private function isPermflagsEnabledWithClient($client, string $mailbox): bool {
		try {
			$capabilities = $client->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
		} catch (Horde_Imap_Client_Exception $e) {
			throw new ServiceException(
				'Could not get message flag options from IMAP: ' . $e->getMessage(),
				$e->getCode(),
				$e,
			);
		}

		return is_array($capabilities)
			&& in_array('\\*', $capabilities['permflags'] ?? [], true);
	}

	private function convertMailQueryToHordeQuery(SearchQuery $searchQuery): Horde_Imap_Client_Search_Query {
		$query = new Horde_Imap_Client_Search_Query();
		foreach ($searchQuery->getBodies() as $textToken) {
			$query->text($textToken, true);
		}

		return $query;
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

	private function applyTagValue(Message $message, Tag $tag, bool $value): void {
		$tags = $message->getTags();

		if ($value) {
			foreach ($tags as $existingTag) {
				if ($existingTag->getImapLabel() === $tag->getImapLabel()) {
					return;
				}
			}

			$new = new Tag();
			$new->setImapLabel($tag->getImapLabel());
			$new->setDisplayName($tag->getDisplayName());
			$new->setColor($tag->getColor());
			$new->setIsDefaultTag($tag->getIsDefaultTag());
			$tags[] = $new;
		} else {
			$tags = array_values(array_filter(
				$tags,
				static fn (Tag $existingTag): bool => $existingTag->getImapLabel() !== $tag->getImapLabel(),
			));
		}

		$message->setTags($tags);
	}
}
