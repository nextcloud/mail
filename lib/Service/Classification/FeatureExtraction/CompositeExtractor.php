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

namespace OCA\Mail\Service\Classification\FeatureExtraction;

use OCA\Mail\Account;

/**
 * Combines a set of DI'ed extractors so they can be used as one class
 */
class CompositeExtractor {
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

	public function extract(string $email): array {
		return array_map(function (IExtractor $extractor) use ($email) {
			return $extractor->extract($email);
		}, $this->extractors);
	}
}
