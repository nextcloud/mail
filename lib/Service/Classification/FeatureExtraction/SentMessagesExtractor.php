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
use OCA\Mail\Db\StatisticsDao;
use RuntimeException;
use function array_map;
use function array_unique;

class SentMessagesExtractor implements IExtractor {
	/** @var StatisticsDao */
	private $statisticsDao;

	/** @var int */
	private $messagesSentTotal = 0;

	/** @var int[] */
	private $messagesSent;

	public function __construct(StatisticsDao $statisticsDao) {
		$this->statisticsDao = $statisticsDao;
	}

	public function prepare(Account $account,
							array $incomingMailboxes,
							array $outgoingMailboxes,
							array $messages): void {
		$senders = array_unique(array_map(static function (Message $message) {
			return $message->getFrom()->first()->getEmail();
		}, array_filter($messages, static function (Message $message) {
			return $message->getFrom()->first() !== null && $message->getFrom()->first()->getEmail() !== null;
		})));

		$this->messagesSentTotal = $this->statisticsDao->getMessagesTotal(...$outgoingMailboxes);
		$this->messagesSent = $this->statisticsDao->getMessagesSentToGrouped($outgoingMailboxes, $senders);
	}

	public function extract(Message $message): array {
		$sender = $message->getFrom()->first();
		if ($sender === null) {
			throw new RuntimeException("This should not happen");
		}
		$email = $sender->getEmail();

		if (($messagesSentTotal = $this->messagesSentTotal) === 0) {
			// Prevent div by 0
			return [0];
		}

		return [($this->messagesSent[$email] ?? 0) / $messagesSentTotal];
	}
}
