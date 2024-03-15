<?php

declare(strict_types=1);
/**
 * @copyright 2024 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Send;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;

class SendHandler extends AHandler {
	public function __construct(private IMailTransmission $transmission,
	) {
	}

	public function process(Account $account, LocalMessage $localMessage): LocalMessage {
		if($localMessage->getStatus() === LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL
			|| $localMessage->getStatus() === LocalMessage::STATUS_PROCESSED) {
			return $this->processNext($account, $localMessage);
		}

		$this->transmission->sendMessage($account, $localMessage);
		// Something went wrong during the sending
		if($localMessage->getStatus() !== LocalMessage::STATUS_RAW) {
			return $localMessage;
		}

		return $this->processNext($account, $localMessage);
	}
}
