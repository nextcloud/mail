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
use OCA\Mail\Db\LocalMessage;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class DraftMessageDeletedEvent extends Event {

	/** @var Account */
	private $account;

	/** @var LocalMessage */
	private $draft;

	public function __construct(Account $account,
								LocalMessage $draft) {
		parent::__construct();
		$this->account = $account;
		$this->draft = $draft;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getDraft(): ?LocalMessage {
		return $this->draft;
	}
}
