<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Model;

use OCA\Mail\Account;
use OCA\Mail\Db\Message;

/**
 * An immutable DTO that holds information about a message that is replied to
 *
 * @psalm-immutable
 */
class RepliedMessageData {
	/** @var Account */
	private $account;

	/** @var Message */
	private $message;

	public function __construct(Account $account, Message  $message) {
		$this->account = $account;
		$this->message = $message;
	}

	public function getAccount(): Account {
		return $this->account;
	}

	public function getMessage(): Message {
		return $this->message;
	}
}
