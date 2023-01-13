<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
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
