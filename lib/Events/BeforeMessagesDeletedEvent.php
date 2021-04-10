<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Events;

use OCA\Mail\Account;
use OCP\EventDispatcher\Event;

class BeforeMessagesDeletedEvent extends Event {

	/** @var Account */
	private $account;

	/** @var string */
	private $folderId;

	/** @var int[] */
	private $messageIds;

	public function __construct(Account $account, string $mailbox, array $messageIds) {
		parent::__construct();
		$this->account = $account;
		$this->folderId = $mailbox;
		$this->messageIds = $messageIds;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getFolderId(): string {
		return $this->folderId;
	}

	/**
	 * @return int[]
	 */
	public function getMessageIds(): array {
		return $this->messageIds;
	}
}
