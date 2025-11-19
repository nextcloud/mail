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

class ImportantMessagesExtractor implements IExtractor {
	/** @var int[] */
	private array $totalMessages = [];

	/** @var int[] */
	private array $flaggedMessages = [];

	public function __construct(
		private readonly \OCA\Mail\Db\StatisticsDao $statisticsDao,
	) {
	}

	#[\Override]
	public function prepare(Account $account,
		array $incomingMailboxes,
		array $outgoingMailboxes,
		array $messages): void {
		/** @var string[] $senders */
		$senders = array_unique(array_map(static fn (Message $message): ?string => $message->getFrom()->first()->getEmail(), array_filter($messages, static fn (Message $message): bool => $message->getFrom()->first() !== null && $message->getFrom()->first()->getEmail() !== null)));
		$this->totalMessages = $this->statisticsDao->getNumberOfMessagesGrouped($incomingMailboxes, $senders);
		$this->flaggedMessages = $this->statisticsDao->getNumberOfMessagesWithFlagGrouped($incomingMailboxes, 'important', $senders);
	}

	#[\Override]
	public function extract(Message $message): array {
		$sender = $message->getFrom()->first();
		if ($sender === null) {
			throw new RuntimeException('This should not happen');
		}
		$email = $sender->getEmail();

		if (($total = $this->totalMessages[$email] ?? 0) === 0) {
			// Prevent div by 0
			return [0];
		}
		return [($this->flaggedMessages[$email] ?? 0) / $total];
	}
}
