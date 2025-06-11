<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\StatisticsDao;
use RuntimeException;
use function array_map;
use function array_unique;

class ReadMessagesExtractor implements IExtractor {
	/** @var StatisticsDao */
	private $statisticsDao;

	/** @var int[] */
	private $totalMessages;

	/** @var int[] */
	private $readMessages;

	public function __construct(StatisticsDao $statisticsDao) {
		$this->statisticsDao = $statisticsDao;
	}

	#[\Override]
	public function prepare(Account $account,
		array $incomingMailboxes,
		array $outgoingMailboxes,
		array $messages): void {
		/** @var string[] $senders */
		$senders = array_unique(array_map(static function (Message $message) {
			return $message->getFrom()->first()->getEmail();
		}, array_filter($messages, static function (Message $message) {
			return $message->getFrom()->first() !== null && $message->getFrom()->first()->getEmail() !== null;
		})));

		$this->totalMessages = $this->statisticsDao->getNumberOfMessagesGrouped($incomingMailboxes, $senders);
		$this->readMessages = $this->statisticsDao->getNumberOfMessagesWithFlagGrouped($incomingMailboxes, 'seen', $senders);
	}

	#[\Override]
	public function extract(Message $message): array {
		$sender = $message->getFrom()->first();
		if ($sender === null) {
			throw new RuntimeException('This should not happen');
		}

		$email = $sender->getEmail();
		$total = $this->totalMessages[$email] ?? 0;

		// Prevent division by zero and just say no emails are read
		if ($total === 0) {
			return [0];
		}

		return [($this->readMessages[$email] ?? 0) / $total];
	}
}
