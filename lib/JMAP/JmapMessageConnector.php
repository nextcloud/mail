<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper as DatabaseMessageMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\ThreadMapper;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Protocol\SyncResult;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCA\Mail\Service\Quota;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\EventDispatcher\IEventDispatcher;
use Psr\Log\LoggerInterface;

class JmapMessageConnector implements IMessageConnector {
	public function __construct(
		private readonly JmapOperationsService $jmapOperationsService,
		private readonly JmapMessageAdapter $jmapMessageAdapter,
		private readonly DatabaseMessageMapper $dbMessageMapper,
		private readonly MailboxMapper $mailboxMapper,
		private readonly ThreadMapper $threadMapper,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function syncAll(Account $account, bool $force = false): void {
		$rebuildThreads = false;
		foreach ($this->mailboxMapper->findAll($account) as $mailbox) {
			$syncSent = $account->getMailAccount()->getSentMailboxId() === $mailbox->getId() || $mailbox->isSpecialUse('sent');
			if (!$mailbox->isInbox() && !$mailbox->getSyncInBackground() && !$syncSent) {
				$this->logger->debug('Skipping mailbox sync for ' . $mailbox->getId());
				continue;
			}

			$this->logger->debug('Syncing ' . $mailbox->getId());
			$this->syncMessages($account, $mailbox, $force);
			$rebuildThreads = true;
		}

		$this->eventDispatcher->dispatchTyped(new SynchronizationEvent($account, $this->logger, $rebuildThreads));
	}

	#[\Override]
	public function syncMailbox(Account $account, Mailbox $mailbox, LoggerInterface $logger, int $criteria, ?array $knownUids = null, bool $force = false): SyncResult {
		if ($mailbox->getRemoteId() === null || $mailbox->getSelectable() === false) {
			return new SyncResult(syncToken: $mailbox->getSyncChangedToken());
		}

		// fetch delta from remote store
		$this->jmapOperationsService->connect($account);
		$delta = $this->jmapOperationsService->entityDelta($mailbox->getRemoteId(), $mailbox->getState() ?? '');
		if ($delta['state'] === $mailbox->getState()) {
			return new SyncResult(state: $mailbox->getState());
		}

		$addedUids = [];
		$addedMessages = [];
		$modifiedUids = [];
		$modifiedMessages = [];
		$deletedUids = [];

		// update local store - deletions
		if (isset($delta['deletions']) && $delta['deletions'] !== []) {
			$deletedMessages = $this->dbMessageMapper->findByRemoteIds($mailbox, $delta['deletions']);
			foreach ($deletedMessages as $key => $message) {
				$deletedUids[] = $message->getUid();
				unset($deletedMessages[$key]);
			}
			$this->dbMessageMapper->deleteByRemoteIds($mailbox, ...$delta['deletions']);
		}

		$deltaIds = array_values(array_unique(array_merge($delta['additions'] ?? [], $delta['modifications'] ?? [])));
		$remoteMessages = $deltaIds === [] ? [] : ($this->jmapOperationsService->entityFetchMessage(...$deltaIds) ?? []);
		$localMessages = $this->dbMessageMapper->findByRemoteIds($mailbox, $deltaIds);
		$localMessages = $this->mapMessagesByRemoteId(...$localMessages);
		
		$nextUid = ($this->dbMessageMapper->findHighestUid($mailbox) ?? 0) + 1;

		foreach (array_keys($remoteMessages) as $remoteId) {
			$remoteMessage = $remoteMessages[$remoteId];
			$localMessage = $localMessages[$remoteId] ?? null;
			$uid = $localMessage?->getUid() ?? $nextUid++;
			if ($localMessage !== null) {
				$modifiedUids[] = $uid;
				$modifiedMessages[] = $this->mergeMessage($localMessage, $remoteMessage);
			} else {
				$remoteMessage->setMailboxId($mailbox->getId());
				$remoteMessage->setUid($uid);
				$addedUids[] = $uid;
				$addedMessages[] = $remoteMessage;
			}
			unset($remoteMessages[$remoteId]);
			unset($localMessages[$remoteId]);
		}

		if ($addedMessages !== []) {
			$this->dbMessageMapper->insertBulk($account, ...$addedMessages);
		}
		if ($modifiedMessages !== []) {
			$this->dbMessageMapper->updateBulk($account, true, ...$modifiedMessages);
		}

		$mailbox->setState($delta['state']);
		$this->mailboxMapper->update($mailbox);

		return new SyncResult(
			new: $addedUids,
			modified: $modifiedUids,
			deleted: $deletedUids,
			state: $mailbox->getState(),
			stats: ['rebuildThreads' => true],
		);
	}

	#[\Override]
	public function fetchMessages(Account $account, Mailbox $mailbox, bool $loadBody = false, Message ...$messages): array {
		$messages = $this->mapMessagesByRemoteId(...$messages);
		// retrieve message details from remote store
		$this->jmapOperationsService->connect($account);
		$remoteMessages = $this->jmapOperationsService->entityFetchNative(...array_keys($messages));
		// convert to model messages and preserve UIDs from local store
		foreach ($remoteMessages as $remoteId => $remoteMessage) {
			$remoteMessages[$remoteId] = $this->jmapMessageAdapter->convertToModelMessage($remoteMessage, $messages[$remoteId]->getUid(), $loadBody);
		}
		return $remoteMessages;
	}

	#[\Override]
	public function findMessages(Account $account, Mailbox $mailbox, SearchQuery $searchQuery): array {
		if ($mailbox->getRemoteId() === null) {
			return [];
		}

		$this->jmapOperationsService->connect($account);
		$results = $this->jmapOperationsService->entityList(
			$mailbox->getRemoteId(),
			$this->convertSearchQueryToFilters($searchQuery),
			null,
			null,
			'basic',
		);
		$messages = $this->dbMessageMapper->findByRemoteIds($mailbox, array_keys($results['list']));

		return array_map(
			static fn (Message $message): int => $message->getUid(),
			$messages,
		);
	}

	#[\Override]
	public function fetchMessageRaw(Account $account, Mailbox $mailbox, Message $message): ?string {
		$remoteId = $message->getRemoteId();
		if ($remoteId === null) {
			throw new ServiceException("Message {$message->getId()} does not have a remote id");
		}
		// retrieve from remote store
		$this->jmapOperationsService->connect($account);
		return $this->jmapOperationsService->entityFetchRaw($remoteId);
	}

	/**
	 * @return Attachment[]
	 */
	#[\Override]
	public function fetchAttachments(Account $account, Mailbox $mailbox, Message $message): array {
		$remoteId = $message->getRemoteId();
		if ($remoteId === null) {
			throw new ServiceException("Message {$message->getId()} does not have a remote id");
		}
		// retrieve from remote store
		$this->jmapOperationsService->connect($account);
		return $this->jmapOperationsService->attachmentFetch($remoteId);
	}

	#[\Override]
	public function fetchAttachment(Account $account, Mailbox $mailbox, Message $message, string $attachmentId): Attachment {
		$remoteId = $message->getRemoteId();
		if ($remoteId === null) {
			throw new ServiceException("Message {$message->getId()} does not have a remote id");
		}
		// retrieve from remote store
		$this->jmapOperationsService->connect($account);
		$attachment = $this->jmapOperationsService->attachmentFetch($remoteId, $attachmentId)[0] ?? null;

		if ($attachment === null) {
			throw new ServiceException("Attachment $attachmentId for message {$message->getId()} could not be retrieved from server");
		}
		return $attachment;
	}

	#[\Override]
	public function moveMessages(Account $account, Mailbox $targetMailbox, Mailbox $sourceMailbox, Message ...$messages): array {
		if ($targetMailbox->getRemoteId() === null) {
			throw new ServiceException("Destination mailbox {$targetMailbox->getId()} does not have a remote id");
		}
		$messages = $this->mapMessagesByRemoteId(...$messages);
		// update remote store
		$this->jmapOperationsService->connect($account);
		$results = $this->jmapOperationsService->entityMove($targetMailbox->getRemoteId(), ...array_keys($messages));
		// compute mutated messages with new mailbox id and uid if move was successful
		$mutatedMessages = [];
		$nextUid = ($this->dbMessageMapper->findHighestUid($targetMailbox) ?? 0) + 1;
		foreach ($results as $remoteId => $status) {
			if (!isset($messages[$remoteId]) || $status !== true) {
				continue;
			}
			$messages[$remoteId]->setMailboxId($targetMailbox->getId());
			$messages[$remoteId]->setUid($nextUid++);
			$mutatedMessages[] = $messages[$remoteId];
		}

		return $mutatedMessages;
	}

	#[\Override]
	public function deleteMessages(Account $account, Mailbox $mailbox, Message ...$messages): array {
		if ($trashMailbox->getRemoteId() === null) {
			throw new ServiceException("Trash mailbox {$trashMailbox->getId()} does not have a remote id");
		}
		$messages = $this->mapMessagesByRemoteId(...$messages);
		// update remote store
		$this->jmapOperationsService->connect($account);
		$this->jmapOperationsService->entityDelete(...array_keys($messages));
		// compute mutated messages with new mailbox id and uid if move was successful
		$mutatedMessages = [];
		$nextUid = ($this->dbMessageMapper->findHighestUid($targetMailbox) ?? 0) + 1;
		foreach ($results as $remoteId => $status) {
			if (!isset($messages[$remoteId]) || $status !== true) {
				continue;
			}
			$messages[$remoteId]->setMailboxId($targetMailbox->getId());
			$messages[$remoteId]->setUid($nextUid++);
			$mutatedMessages[] = $messages[$remoteId];
		}

		return $mutatedMessages;
	}
	
	#[\Override]
	public function flagMessages(Account $account, Mailbox $mailbox, string $flag, bool $value, Message ...$messages): array {
		if ($messages === []) {
			return [];
		}
		$messages = $this->mapMessagesByRemoteId(...$messages);
		$flag = $this->normalizeFlagForRemote($flag);
		// update remote store
		$this->jmapOperationsService->connect($account);
		$results = $this->jmapOperationsService->entityModifyFlags([$flag => $value], ...array_keys($messages));

		$mutatedMessages = [];
		foreach ($messages as $remoteId => $message) {
			if (($results[$remoteId] ?? false) !== true) {
				throw new ServiceException("Message {$message->getUid()} could not be flagged on remote");
			}
			$this->applyFlagValue($message, $flag, $value);
			$mutatedMessages[] = $message;
		}

		return $mutatedMessages;
	}

	#[\Override]
	public function tagMessages(Account $account, Mailbox $mailbox, Tag $tag, bool $value, Message ...$messages): array {
		return $this->flagMessages($account, $mailbox, $tag->getImapLabel(), $value, ...$messages);
	}

	#[\Override]
	public function getQuota(Account $account): ?Quota {
		return null;
	}

	#[\Override]
	public function clearCache(Account $account, Mailbox $mailbox): void {
		$this->dbMessageMapper->deleteAll($mailbox);
		$mailbox->setState(null);
		$this->mailboxMapper->update($mailbox);
	}

	#[\Override]
	public function repairSync(Account $account, Mailbox $mailbox): void {
		$this->clearCache($account, $mailbox);
		$this->logger->debug('Repairing JMAP mailbox cache for ' . $mailbox->getId());
		$this->syncMessages($account, $mailbox, true);
	}

	#[\Override]
	public function isPermflagsEnabled(Account $account, Mailbox $mailbox): bool {
		return true;
	}

	private function convertSearchQueryToFilters(SearchQuery $searchQuery): array {
		$filters = [];
		foreach ($searchQuery->getBodies() as $textToken) {
			$filters[] = [
				'attribute' => 'body',
				'value' => $textToken,
			];
		}

		return $filters;
	}

	private function mapMessagesByRemoteId(Message ...$messages): array {
		$mapped = [];
		foreach ($messages as $message) {
			$rid = $message->getRemoteId();
			if ($rid === null) {
				throw new ServiceException("Message {$message->getId()} does not have a remote id");
			}
			$mapped[$rid] = $message;
		}
		return $mapped;
	}

	private function mergeMessage(Message $target, Message $source): Message {
		$target->setRemoteId($source->getRemoteId());
		$target->setMessageId($source->getMessageId());
		$target->setInReplyTo($source->getInReplyTo());
		$target->setReferences($source->getReferences());
		$target->setThreadRootId($source->getThreadRootId());
		$target->setSubject($source->getSubject());
		$target->setSentAt($source->getSentAt());
		$target->setFlagAnswered($source->getFlagAnswered() === true);
		$target->setFlagDeleted($source->getFlagDeleted() === true);
		$target->setFlagDraft($source->getFlagDraft() === true);
		$target->setFlagFlagged($source->getFlagFlagged() === true);
		$target->setFlagSeen($source->getFlagSeen() === true);
		$target->setFlagForwarded($source->getFlagForwarded() === true);
		$target->setFlagJunk($source->getFlagJunk() === true);
		$target->setFlagNotjunk($source->getFlagNotjunk() === true);
		$target->setFlagImportant($source->getFlagImportant() === true);
		$target->setFlagMdnsent($source->getFlagMdnsent() === true);
		$target->setPreviewText($source->getPreviewText());
		$target->setFlagAttachments($source->getFlagAttachments());
		$target->setStructureAnalyzed($source->getStructureAnalyzed() === true);
		$target->setUpdatedAt($source->getUpdatedAt());
		$target->setFrom($source->getFrom());
		$target->setTo($source->getTo());
		$target->setCc($source->getCc());
		$target->setBcc($source->getBcc());
		$target->setTags($source->getTags());

		return $target;
	}

	private function findLocalMessageByUid(Mailbox $mailbox, int $uid): Message {
		$messages = $this->dbMessageMapper->findByUids($mailbox, [$uid]);
		if ($messages === []) {
			throw new ServiceException("Message $uid does not exist locally");
		}

		return $messages[0];
	}

	private function normalizeFlagForRemote(string $flag): string {
		return match ($flag) {
			'seen' => '$seen',
			'flagged' => '$flagged',
			'deleted' => '$deleted',
			'draft' => '$draft',
			'answered' => '$answered',
			'forwarded' => '$forwarded',
			'junk' => '$junk',
			'notjunk' => '$notjunk',
			'mdnsent' => '$mdnsent',
			'important' => Tag::LABEL_IMPORTANT,
			default => $flag,
		};
	}

	private function applyFlagValue(Message $message, string $flag, bool $value): void {
		switch ($flag) {
			case '$seen':
			case '$flagged':
			case '$deleted':
			case '$draft':
			case '$answered':
			case '$forwarded':
				$message->setFlag(ltrim($flag, '$'), $value);
				break;
			case '$junk':
			case '$notjunk':
			case '$phishing':
			case '$mdnsent':
			case Tag::LABEL_IMPORTANT:
				$message->setFlag($flag, $value);
				break;
			default:
				$this->applyTagValue($message, $flag, $value);
				break;
		}
	}

	private function applyTagValue(Message $message, string $flag, bool $value): void {
		$tags = $message->getTags();

		if ($value) {
			foreach ($tags as $tag) {
				if ($tag->getImapLabel() === $flag) {
					return;
				}
			}

			$tag = new Tag();
			$tag->setImapLabel($flag);
			$tag->setDisplayName($flag);
			$tag->setColor('');
			$tag->setIsDefaultTag(false);
			$tags[] = $tag;
		} else {
			$tags = array_values(array_filter(
				$tags,
				static fn (Tag $tag): bool => $tag->getImapLabel() !== $flag,
			));
		}

		$message->setTags($tags);
	}

}
