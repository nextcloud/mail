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
abstract class CompositeExtractor implements IExtractor {
	/** @var IExtractor[] */
	protected array $extractors;

	/**
	 * @param IExtractor[] $extractors
	 */
	public function __construct(array $extractors) {
		$this->extractors = $extractors;
	}

	public function prepare(Account $account,
		array $incomingMailboxes,
		array $outgoingMailboxes,
		array $messages): void {
		foreach ($this->extractors as $extractor) {
			$extractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function extract(Message $message): array {
		return array_flat_map(static function (IExtractor $extractor) use ($message) {
			return $extractor->extract($message);
		}, $this->extractors);
	}
}
