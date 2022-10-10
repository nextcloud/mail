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
use OCA\Mail\Db\Message;
use OCA\Mail\Model\NewMessageData;
use OCP\EventDispatcher\Event;

class DraftSavedEvent extends Event {
	/** @var Account */
	private $account;

	/** @var NewMessageData */
	private $newMessageData;

	/** @var Message|null */
	private $draft;

	public function __construct(Account $account,
								NewMessageData $newMessageData,
								?Message $draft) {
		parent::__construct();
		$this->account = $account;
		$this->newMessageData = $newMessageData;
		$this->draft = $draft;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getNewMessageData(): NewMessageData {
		return $this->newMessageData;
	}

	public function getDraft(): ?Message {
		return $this->draft;
	}
}
