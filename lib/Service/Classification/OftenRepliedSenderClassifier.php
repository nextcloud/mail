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
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\StatisticsDao;
use OCP\AppFramework\Db\DoesNotExistException;

class OftenRepliedSenderClassifier extends AClassifier {
	use SafeRatio;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var StatisticsDao */
	private $statisticsDao;

	public function __construct(MailboxMapper $mailboxMapper,
								StatisticsDao $statisticsDao) {
		$this->mailboxMapper = $mailboxMapper;
		$this->statisticsDao = $statisticsDao;
	}

	public function isImportant(Account $account, Mailbox $mailbox, Message $message): bool {
		$sender = $message->getTo()->first();
		if ($sender === null) {
			return false;
		}

		try {
			$mb = $this->mailboxMapper->findSpecial($account, 'inbox');
		} catch (DoesNotExistException $e) {
			return false;
		}

		return $this->greater(
			$this->statisticsDao->getNrOfRepliedMessages($mb, $sender->getEmail()),
			$this->statisticsDao->getNumberOfMessages($mb, $sender->getEmail()),
			0.1
		);
	}
}
