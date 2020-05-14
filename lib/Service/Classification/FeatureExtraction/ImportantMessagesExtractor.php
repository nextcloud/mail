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
use OCA\Mail\Db\Message;
use OCA\Mail\Db\StatisticsDao;
use function array_map;
use function array_unique;

class ImportantMessagesExtractor implements IExtractor {

	/** @var int */
	private $totalMessages = 0;

	/** @var int[] */
	private $flaggedMessages = [];

	/** @var StatisticsDao */
	private $statisticsDao;

	public function __construct(StatisticsDao $statisticsDao) {
		$this->statisticsDao = $statisticsDao;
	}

	public function prepare(Account $account,
							array $incomingMailboxes,
							array $outgoingMailboxes,
							array $messages): bool {
		$senders = array_unique(array_map(function (Message $message) {
			return $message->getFrom()->first()->getEmail();
		}, $messages));
		$this->totalMessages = $this->statisticsDao->getNumberOfMessagesGrouped($incomingMailboxes, $senders);
		$this->flaggedMessages = $this->statisticsDao->getNumberOfMessagesWithFlagGrouped($incomingMailboxes, 'important', $senders);

		// This extractor is only applicable if there are incoming messages
		return $this->totalMessages > 0;
	}

	public function extract(string $email): float {
		if (($total = $this->totalMessages[$email] ?? 0) === 0) {
			// Prevent div by 0
			return 0;
		}
		return ($this->flaggedMessages[$email] ?? 0) / $total;
	}
}
