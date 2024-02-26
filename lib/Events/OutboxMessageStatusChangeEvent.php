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

namespace OCA\Mail\Events;

use OCA\Mail\Db\LocalMessage;
use OCP\EventDispatcher\Event;

/**
 * @psalm-immutable
 */
class OutboxMessageStatusChangeEvent extends Event {

	public function __construct(private LocalMessage $message) {
	}

	public function getMessage(): LocalMessage {
		return $this->message;
	}

	public function setMessage(LocalMessage $message): void {
		$this->message = $message;
	}
}
