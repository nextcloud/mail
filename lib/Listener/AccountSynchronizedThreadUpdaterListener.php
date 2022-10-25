<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\Listener;

use Generator;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Events\SynchronizationEvent;
use OCA\Mail\IMAP\Threading\Container;
use OCA\Mail\IMAP\Threading\DatabaseMessage;
use OCA\Mail\IMAP\Threading\ThreadBuilder;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use function array_chunk;
use function iterator_to_array;

/**
 * @template-implements IEventListener<Event|SynchronizationEvent>
 */
class AccountSynchronizedThreadUpdaterListener implements IEventListener {
	private const WRITE_IDS_CHUNK_SIZE = 500;

	/** @var MessageMapper */
	private $mapper;

	/** @var ThreadBuilder */
	private $builder;

	public function __construct(MessageMapper $mapper,
								ThreadBuilder $builder) {
		$this->mapper = $mapper;
		$this->builder = $builder;
	}

	public function handle(Event $event): void {
		if (!($event instanceof SynchronizationEvent)) {
			// Unrelated
			return;
		}
		$logger = $event->getLogger();
		if (!$event->isRebuildThreads()) {
			$event->getLogger()->debug('Skipping threading as there were no significant changes');
			return;
		}

		$accountId = $event->getAccount()->getId();
		$logger->debug("Building threads for account $accountId");
		$messages = $this->mapper->findThreadingData($event->getAccount());
		$logger->debug("Account $accountId has " . count($messages) . " messages with threading information");
		$threads = $this->builder->build($messages, $logger);
		$logger->debug("Account $accountId has " . count($threads) . " threads");
		/** @var DatabaseMessage[] $flattened */
		$flattened = iterator_to_array($this->flattenThreads($threads), false);
		$logger->debug("Account $accountId has " . count($flattened) . " messages with a new thread IDs");
		foreach (array_chunk($flattened, self::WRITE_IDS_CHUNK_SIZE) as $chunk) {
			$this->mapper->writeThreadIds($chunk);

			$logger->debug("Chunk of " . self::WRITE_IDS_CHUNK_SIZE . " messages updated");
		}
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
