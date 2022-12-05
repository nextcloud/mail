<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2021 Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\IMAP;

use JsonSerializable;
use ReturnTypeWillChange;

class MailboxStats implements JsonSerializable {
	private int $total;
	private int $unread;
	private ?string $myAcls;

	public function __construct(int $total, int $unread, ?string $myAcls) {
		$this->total = $total;
		$this->unread = $unread;
		$this->myAcls = $myAcls;
	}

	public function getTotal(): int {
		return $this->total;
	}

	public function getUnread(): int {
		return $this->unread;
	}

	public function getMyAcls(): ?string {
		return $this->myAcls;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'total' => $this->total,
			'unread' => $this->unread,
			'myAcls' => $this->myAcls,
		];
	}
}
