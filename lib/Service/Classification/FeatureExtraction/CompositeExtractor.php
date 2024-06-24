<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;
use function OCA\Mail\array_flat_map;

/**
 * Combines a set of DI'ed extractors so they can be used as one class
 */
class CompositeExtractor implements IExtractor {
	/** @var IExtractor[] */
	private $extractors;

	public function __construct(ImportantMessagesExtractor $ex1,
		ReadMessagesExtractor $ex2,
		RepliedMessagesExtractor $ex3,
		SentMessagesExtractor $ex4) {
		$this->extractors = [
			$ex1,
			$ex2,
			$ex3,
			$ex4,
		];
	}

	public function prepare(Account $account,
		array $incomingMailboxes,
		array $outgoingMailboxes,
		array $messages): void {
		foreach ($this->extractors as $extractor) {
			$extractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);
		}
	}

	public function extract(Message $message): array {
		return array_flat_map(static function (IExtractor $extractor) use ($message) {
			return $extractor->extract($message);
		}, $this->extractors);
	}
}
