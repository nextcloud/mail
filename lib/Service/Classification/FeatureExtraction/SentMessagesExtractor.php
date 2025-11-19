<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use RuntimeException;
use function array_map;
use function array_unique;

class SentMessagesExtractor implements IExtractor {
	private int $messagesSentTotal = 0;

	/** @var int[] */
	private ?array $messagesSent = null;

	public function __construct(
		private readonly \OCA\Mail\Db\StatisticsDao $statisticsDao,
	) {
	}

	#[\Override]
	public function prepare(Account $account,
		array $incomingMailboxes,
		array $outgoingMailboxes,
		array $messages): void {
		$senders = array_unique(array_map(static fn (Message $message): ?string => $message->getFrom()->first()->getEmail(), array_filter($messages, static fn (Message $message): bool => $message->getFrom()->first() !== null && $message->getFrom()->first()->getEmail() !== null)));

		$this->messagesSentTotal = $this->statisticsDao->getMessagesTotal(...$outgoingMailboxes);
		$this->messagesSent = $this->statisticsDao->getMessagesSentToGrouped($outgoingMailboxes, $senders);
	}

	#[\Override]
	public function extract(Message $message): array {
		$sender = $message->getFrom()->first();
		if ($sender === null) {
			throw new RuntimeException('This should not happen');
		}
		$email = $sender->getEmail();

		if (($messagesSentTotal = $this->messagesSentTotal) === 0) {
			// Prevent div by 0
			return [0];
		}

		return [($this->messagesSent[$email] ?? 0) / $messagesSentTotal];
	}
}
