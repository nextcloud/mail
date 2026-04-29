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
use OCA\Mail\Events\BeforeMessageDeletedEvent;
use OCA\Mail\Events\MessageDeletedEvent;
use OCA\Mail\Events\MessageFlaggedEvent;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\TrashMailboxNotSetException;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Protocol\SyncResult;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCA\Mail\Service\Quota;
use OCP\AppFramework\Db\DoesNotExistException;
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
	public function syncAccount(Account $account, bool $force = false): void {
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
	public function syncMessages(Account $account, Mailbox $mailbox, bool $force = false): SyncResult {
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
		$remoteMessages = $deltaIds === [] ? [] : ($this->jmapOperationsService->entityFetchDatabaseMessage(...$deltaIds) ?? []);
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
	public function syncMailbox(Account $account, Mailbox $mailbox, LoggerInterface $logger, int $criteria, ?array $knownUids = null, bool $force = false): SyncResult {
		return $this->syncMessages($account, $mailbox, $force);
	}

	#[\Override]
	public function clearCache(Account $account, Mailbox $mailbox): void {
		$this->dbMessageMapper->deleteAll($mailbox);
		$mailbox->setState(null);
		$this->mailboxMapper->update($mailbox);
	}

	#[\Override]
	public function repairSync(Account $account, Mailbox $mailbox, LoggerInterface $logger): void {
		$this->clearCache($account, $mailbox);
		$this->logger->debug('Repairing JMAP mailbox cache for ' . $mailbox->getId());
		$this->syncMessages($account, $mailbox, true);
	}

	#[\Override]
	public function fetchMessage(Account $account, Mailbox $mailbox, int $uid, bool $loadBody = false): IMAPMessage {
		$message = $this->findLocalMessageByUid($mailbox, $uid);
		$rid = $message->getRemoteId();
		if ($rid === null) {
			throw new ServiceException("Message $uid does not have a JMAP remote id");
		}
		// retrieve message details from remote store
		$this->jmapOperationsService->connect($account);
		$remoteMessages = $this->jmapOperationsService->entityFetchNative($rid);
		$remoteMessage = $remoteMessages[$rid] ?? null;
		if ($remoteMessage === null) {
			throw new ServiceException("Message $uid with remote id $rid could not be fetched from JMAP");
		}
		
		return $this->jmapMessageAdapter->convertToModelMessage($remoteMessage, $uid, $loadBody);
	}

	#[\Override]
	public function fetchMessageRaw(Account $account, Mailbox $mailbox, int $uid): ?string {
		$message = $this->findLocalMessageByUid($mailbox, $uid);
		$rid = $message->getRemoteId();
		if ($rid === null) {
			throw new ServiceException("Message $uid does not have a JMAP remote id");
		}
		// retrieve from remote store
		$this->jmapOperationsService->connect($account);
		return $this->jmapOperationsService->entityFetchRaw($rid);
	}

	/**
	 * @return Attachment[]
	 */
	#[\Override]
	public function fetchAttachments(Account $account, Mailbox $mailbox, int $uid): array {
		$message = $this->fetchMessage($account, $mailbox, $uid, true);
		return array_map(
			static fn (array $attachment): Attachment => new Attachment(
				$attachment['id'] ?? null,
				$attachment['fileName'] ?? null,
				$attachment['mime'] ?? 'application/octet-stream',
				'',
				(int)($attachment['size'] ?? 0),
			),
			$message->attachments,
		);
	}

	#[\Override]
	public function fetchAttachment(Account $account, Mailbox $mailbox, int $uid, string $attachmentId): Attachment {
		foreach ($this->fetchAttachments($account, $mailbox, $uid) as $attachment) {
			if ($attachment->getId() === $attachmentId) {
				return $attachment;
			}
		}

		throw new ServiceException("Attachment $attachmentId does not exist on message $uid");
	}

	#[\Override]
	public function moveMessage(Account $account, string $sourceMailbox, int $uid, string $destMailbox): ?int {
		// find local message and corresponding remote id
		$source = $this->mailboxMapper->find($account, $sourceMailbox);
		$destination = $this->mailboxMapper->find($account, $destMailbox);
		$message = $this->findLocalMessageByUid($source, $uid);
		$rid = $message->getRemoteId();
		if ($rid === null || $destination->getRemoteId() === null) {
			throw new ServiceException("Message $uid cannot be moved on JMAP");
		}
		// update remote store
		$this->jmapOperationsService->connect($account);
		$results = $this->jmapOperationsService->entityMove($destination->getRemoteId(), $rid);
		if (($results[$rid] ?? false) !== true) {
			throw new ServiceException("Message $uid could not be moved on JMAP");
		}
		// update local store
		$newUid = ($this->dbMessageMapper->findHighestUid($destination) ?? 0) + 1;
		$message->setMailboxId($destination->getId());
		$message->setUid($newUid);
		$this->dbMessageMapper->update($message);

		return $newUid;
	}

	#[\Override]
	public function deleteMessage(Account $account, Mailbox $mailbox, int $uid): void {
		$this->eventDispatcher->dispatchTyped(new BeforeMessageDeletedEvent($account, $mailbox->getName(), $uid));
		// find local message and corresponding remote id
		$message = $this->findLocalMessageByUid($mailbox, $uid);
		$remoteId = $message->getRemoteId();
		if ($remoteId === null) {
			throw new ServiceException("Message $uid does not have a JMAP remote id");
		}
		// find trash mailbox
		try {
			$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
			if ($trashMailboxId === null) {
				throw new TrashMailboxNotSetException();
			}
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('No trash folder', 0, $e);
		}
		// update remote store and local store
		$this->jmapOperationsService->connect($account);
		if ($mailbox->getId() === $trashMailbox->getId()) {
			$results = $this->jmapOperationsService->entityDelete($remoteId);
			if (($results[$remoteId] ?? false) !== true) {
				throw new ServiceException("Message $uid could not be deleted on JMAP");
			}
			$this->dbMessageMapper->deleteByUid($mailbox, $uid);
		} else {
			if ($trashMailbox->getRemoteId() === null) {
				throw new ServiceException('Trash mailbox does not have a JMAP remote id');
			}
			$results = $this->jmapOperationsService->entityMove($trashMailbox->getRemoteId(), $remoteId);
			if (($results[$remoteId] ?? false) !== true) {
				throw new ServiceException("Message $uid could not be moved to trash on JMAP");
			}
		}
		// dispatch event
		$this->eventDispatcher->dispatchTyped(new MessageDeletedEvent($account, $mailbox, $uid));
	}

	/**
	 * Finds all messages in the thread of the given thread root id
	 * 
	 * @return array<string, Message> array of messages in the thread, keyed by remote id
	 */
	private function fetchThread(Account $account, Mailbox $mailbox, string $threadRootId): array {
		$mailAccount = $account->getMailAccount();
		$messageInTrash = $mailbox->getId() === $mailAccount->getTrashMailboxId();
		$threadMessages = $this->threadMapper->findMessageUidsAndMailboxNamesByAccountAndThreadRoot(
			$mailAccount,
			$threadRootId,
			$messageInTrash,
		);

		// group message uids by mailbox
		$uids = [];
		foreach ($threadMessages as $threadMessage) {
			$uids[$threadMessage['mailboxName']][] = $threadMessage['messageUid'];
		}
		unset($threadMessages);
		// retrieve messages from local store
		$messages = [];
		foreach ($uids as $mailboxName => $messageUids) {
			$sourceMailbox = $mailboxes[$mailboxName] ??= $this->mailboxMapper->find($account, $mailboxName);
			$sourceMessages = $this->dbMessageMapper->findByUids($sourceMailbox, $messageUids);
			$messages = array_merge($messages, $sourceMessages);
		}
		$messages = $this->mapMessagesByRemoteId(...$messages);

		return $messages;
	}

	#[\Override]
	public function moveThread(Account $srcAccount, Mailbox $srcMailbox, Account $dstAccount, Mailbox $dstMailbox, string $threadRootId): array {
		if ($srcAccount->getId() !== $dstAccount->getId()) {
			throw new ServiceException('It is not possible to move across accounts yet');
		}
		if ($dstMailbox->getRemoteId() === null) {
			throw new ServiceException('Destination mailbox does not have a JMAP remote id');
		}
		// retrieve all messages for the thread from the local store, keyed by remote id
		$messages = $this->fetchThread($srcAccount, $srcMailbox, $threadRootId);
		if ($messages === []) {
			return [];
		}
		// update remote store
		$this->jmapOperationsService->connect($srcAccount);
		$results = $this->jmapOperationsService->entityMove($dstMailbox->getRemoteId(), ...array_keys($messages));
		// update local store
		$mutatedUids = [];
		$nextUid = ($this->dbMessageMapper->findHighestUid($dstMailbox) ?? 0) + 1;
		foreach ($results as $remoteId => $status) {
			if (!isset($messages[$remoteId]) || $status !== true) {
				continue;
			}
			$messages[$remoteId]->setMailboxId($dstMailbox->getId());
			$messages[$remoteId]->setUid($nextUid++);
			$mutatedUids[] = $messages[$remoteId]->getUid();
		}
		// update local store
		if ($mutatedUids !== []) {
			$updateMessages = array_filter($messages, static fn (Message $message) => in_array($message->getUid(), $mutatedUids, true));
			$this->dbMessageMapper->updateBulk($srcAccount, false, ...$updateMessages);
		}

		return $mutatedUids;
	}

	#[\Override]
	public function deleteThread(Account $account, Mailbox $sourceMailbox, string $threadRootId): void {
		try {
			$trashMailboxId = $account->getMailAccount()->getTrashMailboxId();
			if ($trashMailboxId === null) {
				throw new TrashMailboxNotSetException();
			}
			$trashMailbox = $this->mailboxMapper->findById($trashMailboxId);
		} catch (DoesNotExistException $e) {
			throw new ServiceException('No trash folder', 0, $e);
		}
		if ($trashMailbox->getRemoteId() === null) {
			throw new ServiceException('Trash mailbox does not have a JMAP remote id');
		}

		$operation = $sourceMailbox->getId() === $trashMailbox->getId() ? 'delete' : 'move';
		// retrieve all messages for the thread from the local store, keyed by remote id
		$messages = $this->fetchThread($account, $mailbox, $threadRootId);
		if ($messages === []) {
			return;
		}
		// dispatch events
		foreach ($messages as $message) {
			$this->eventDispatcher->dispatchTyped(new BeforeMessageDeletedEvent($account, $message->getMailboxId(), $message->getUid()));
			$this->logger->debug("$operation message", ['messageId' => $message->getUid(), 'mailboxId' => $message->getMailboxId()]);
		}
		// update remote store
		$this->jmapOperationsService->connect($account);
		$results = match ($operation) {
			'delete' => $this->jmapOperationsService->entityDelete(...array_keys($messages)),
			'move' => $this->jmapOperationsService->entityMove($trashMailbox->getRemoteId(), ...array_keys($messages)),
		};

		// compute updated uids for messages that were successfully moved or deleted
		// update message objects with new mailbox id if they were moved
		$mutatedUids = [];
		$nextUid = ($this->dbMessageMapper->findHighestUid($dstMailbox) ?? 0) + 1;
		foreach ($results as $remoteId => $status) {
			if (!isset($messages[$remoteId]) || $status !== true) {
				continue;
			}
			if ($operation === 'move') {
				$messages[$remoteId]->setMailboxId($trashMailbox->getId());
				$messages[$remoteId]->setUid($nextUid++);
			}
			$mutatedUids[] = $messages[$remoteId]->getUid();
		}
		// update local store
		if ($operation === 'move') {
			$updateMessages = array_filter($messages, static fn (Message $message) => in_array($message->getUid(), $mutatedUids, true));
			$this->dbMessageMapper->updateBulk($account, false, ...$updateMessages);
		}
		if ($operation === 'delete') {
			$this->dbMessageMapper->deleteByUid($mailbox, ...$mutatedUids);
		}
		// dispatch events
		foreach ($results as $remoteId => $status) {
			if (!isset($messages[$remoteId]) || $status !== true) {
				continue;
			}
			$this->eventDispatcher->dispatchTyped(new MessageDeletedEvent($account, $messages[$remoteId]->getMailboxId(), $messages[$remoteId]->getUid()));
		}
	}

	#[\Override]
	public function flagMessages(Account $account, string $flag, bool $value, Message ...$messages): void {
		if ($messages === []) {
			return;
		}
		$messages = $this->mapMessagesByRemoteId(...$messages);
		$flag = $this->normalizeFlagForRemote($flag);
		// update remote store
		$this->jmapOperationsService->connect($account);
		$results = $this->jmapOperationsService->entityModifyFlags([$flag => $value], ...array_keys($messages));

		$updatedMessages = [];
		foreach ($messages as $remoteId => $message) {
			if (($results[$remoteId] ?? false) !== true) {
				throw new ServiceException("Message {$message->getUid()} could not be flagged on remote");
			}
			$this->applyFlagValue($message, $flag, $value);
			$updatedMessages[] = $message;
		}

		$this->dbMessageMapper->updateBulk($account, true, ...$updatedMessages);

		$mailboxes = [];
		foreach ($this->mailboxMapper->findByIds(array_values(array_unique(array_map(
			static fn (Message $message): int => $message->getMailboxId(),
			$updatedMessages,
		)))) as $mailbox) {
			$mailboxes[$mailbox->getId()] = $mailbox;
		}

		foreach ($updatedMessages as $message) {
			$mailbox = $mailboxes[$message->getMailboxId()] ?? null;
			if ($mailbox === null) {
				throw new ServiceException("Mailbox {$message->getMailboxId()} does not exist locally");
			}

			$this->eventDispatcher->dispatchTyped(new MessageFlaggedEvent($account, $mailbox, $message->getUid(), $flag, $value));
		}
	}

	#[\Override]
	public function tagMessages(Account $account, Tag $tag, bool $value, Message ...$messages): void {
		$this->flagMessages($account, $tag->getImapLabel(), $value, ...$messages);
	}

	#[\Override]
	public function clearMailbox(Account $account, Mailbox $mailbox): void {
		$rids = array_map(
			static fn (Message $message): ?string => $message->getRemoteId(),
			$this->dbMessageMapper->findByUids($mailbox, $this->dbMessageMapper->findAllUids($mailbox)),
		);
		$rids = array_values(array_filter($rids, static fn (?string $rid): bool => $rid !== null));

		$this->jmapOperationsService->connect($account);
		if ($rids !== []) {
			$this->jmapOperationsService->entityDelete(...$rids);
		}
		$this->dbMessageMapper->deleteAll($mailbox);
	}

	#[\Override]
	public function getQuota(Account $account): ?Quota {
		return null;
	}

	#[\Override]
	public function isPermflagsEnabled(Account $account, string $mailbox): bool {
		return true;
	}

	private function mapMessagesByRemoteId(Message ...$messages): array {
		$mapped = [];
		foreach ($messages as $message) {
			$rid = $message->getRemoteId();
			if ($rid !== null) {
				$mapped[$rid] = $message;
			}
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
				$message->setFlagSeen($value);
				break;
			case '$flagged':
				$message->setFlagFlagged($value);
				break;
			case '$deleted':
				$message->setFlagDeleted($value);
				break;
			case '$draft':
				$message->setFlagDraft($value);
				break;
			case '$answered':
				$message->setFlagAnswered($value);
				break;
			case '$forwarded':
				$message->setFlagForwarded($value);
				break;
			case '$junk':
				$message->setFlagJunk($value);
				break;
			case '$notjunk':
				$message->setFlagNotjunk($value);
				break;
			case '$important':
			case Tag::LABEL_IMPORTANT:
				$message->setFlagImportant($value);
				break;
			case '$mdnsent':
				$message->setFlagMdnsent($value);
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
