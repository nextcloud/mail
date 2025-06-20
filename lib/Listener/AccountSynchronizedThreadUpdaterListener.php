<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use Generator;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\IMAP\Threading\Container;
use OCA\Mail\IMAP\Threading\DatabaseMessage;
use OCA\Mail\IMAP\Threading\ThreadBuilder;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

use function array_chunk;
use function gc_collect_cycles;
use function iterator_to_array;

/**
 * @template-implements IEventListener<Event|SynchronizationEvent>
 */
class AccountSynchronizedThreadUpdaterListener implements IEventListener {
	private const WRITE_IDS_CHUNK_SIZE = 500;

	public function __construct(
		private IUserPreferences $preferences,
		private MessageMapper $mapper,
		private ThreadBuilder $builder,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof SynchronizationEvent)) {
			// Unrelated
			return;
		}
		$logger = $event->getLogger();
		$userId = $event->getAccount()->getUserId();

		if ($this->preferences->getPreference($userId, 'layout-message-view', 'threaded') !== 'threaded') {
			$event->getLogger()->debug('Skipping threading as the user prefers a flat view');
			return;
		}

		if (!$event->isRebuildThreads()) {
			$event->getLogger()->debug('Skipping threading as there were no significant changes');
			return;
		}

		$accountId = $event->getAccount()->getId();
		$logger->debug("Building threads for account $accountId");
		$messages = $this->mapper->findThreadingData($event->getAccount());
		$logger->debug("Account $accountId has " . count($messages) . ' messages with threading information');
		$threads = $this->builder->build($messages, $logger);
		$logger->debug("Account $accountId has " . count($threads) . ' threads');
		/** @var DatabaseMessage[] $flattened */
		$flattened = iterator_to_array($this->flattenThreads($threads), false);
		$logger->debug("Account $accountId has " . count($flattened) . ' messages with a new thread IDs');
		foreach (array_chunk($flattened, self::WRITE_IDS_CHUNK_SIZE) as $chunk) {
			$this->mapper->writeThreadIds($chunk);

			$logger->debug('Chunk of ' . self::WRITE_IDS_CHUNK_SIZE . ' messages updated');
		}

		// Free memory
		unset($flattened, $threads, $messages);
		gc_collect_cycles();
	}

	/**
	 * @param Container[] $threads
	 *
	 * @return Generator
	 * @psalm-return Generator<int, DatabaseMessage>
	 */
	private function flattenThreads(array $threads,
		?string $threadId = null): Generator {
		foreach ($threads as $thread) {
			if (($message = $thread->getMessage()) !== null) {
				/** @var DatabaseMessage $message */
				if ($threadId === null) {
					// No parent -> let's use own ID
					$message->setThreadRootId($message->getId());
				} else {
					$message->setThreadRootId($threadId);
				}
				if ($message->isDirty()) {
					yield $message;
				}
			}

			yield from $this->flattenThreads(
				$thread->getChildren(),
				$threadId ?? ($message === null ? $thread->getId() : $message->getId())
			);
		}
	}
}
