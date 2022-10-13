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

namespace OCA\Mail\Service\Classification;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\Classification\FeatureExtraction\ImportantMessagesExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\ReadMessagesExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\RepliedMessagesExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\SentMessagesExtractor;
use function array_combine;
use function array_map;

class ImportanceRulesClassifier {
	/** @var ImportantMessagesExtractor */
	private $importantMessagesExtractor;

	/** @var ReadMessagesExtractor */
	private $readMessagesExtractor;

	/** @var RepliedMessagesExtractor */
	private $repliedMessagesExtractor;

	/** @var SentMessagesExtractor */
	private $sentMessagesExtractor;

	public function __construct(ImportantMessagesExtractor $importantMessagesExtractor,
								ReadMessagesExtractor $readMessagesExtractor,
								RepliedMessagesExtractor $repliedMessagesExtractor,
								SentMessagesExtractor $sentMessagesExtractor) {
		$this->importantMessagesExtractor = $importantMessagesExtractor;
		$this->readMessagesExtractor = $readMessagesExtractor;
		$this->repliedMessagesExtractor = $repliedMessagesExtractor;
		$this->sentMessagesExtractor = $sentMessagesExtractor;
	}

	/**
	 * @param Account $account
	 * @param Mailbox[] $incomingMailboxes
	 * @param Mailbox[] $outgoingMailboxes
	 * @param Message[] $messages
	 *
	 * @return bool[]
	 */
	public function classifyImportance(Account $account,
									   array $incomingMailboxes,
									   array $outgoingMailboxes,
									   array $messages): array {
		$this->importantMessagesExtractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);
		$this->readMessagesExtractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);
		$this->repliedMessagesExtractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);
		$this->sentMessagesExtractor->prepare($account, $incomingMailboxes, $outgoingMailboxes, $messages);

		return array_combine(
			array_map(function (Message $m) {
				return $m->getUid();
			}, $messages),
			array_map(function (Message $m) {
				$from = $m->getFrom()->first();
				if ($from === null || $from->getEmail() === null) {
					return false;
				}

				return $this->importantMessagesExtractor->extract($from->getEmail()) > 0.3
					|| $this->readMessagesExtractor->extract($from->getEmail()) > 0.7
					|| $this->repliedMessagesExtractor->extract($from->getEmail()) > 0.1
					|| $this->sentMessagesExtractor->extract($from->getEmail()) > 0.1;
			}, $messages)
		);
	}
}
