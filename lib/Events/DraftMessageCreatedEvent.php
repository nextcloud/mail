<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author 2022 Anna Larch <anna.larch@gmx.net>
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
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class DraftMessageCreatedEvent extends Event {
	/** @var Account */
	private $account;

	/** @var Message */
	private $draft;

	public function __construct(Account $account,
								Message $draft) {
		parent::__construct();
		$this->account = $account;
		$this->draft = $draft;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getDraft(): ?Message {
		return $this->draft;
	}
}
