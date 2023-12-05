<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method void setMailboxId(int $mailboxId)
 * @method int getMailboxId()
 * @method void setUid(int $uid)
 * @method int getUid()
 * @method void setKnownSince(int $knownSince)
 * @method int getKnownSince()
 */
class MessageRetention extends Entity {

	/** @var int */
	protected $mailboxId;

	/** @var int */
	protected $uid;

	/** @var int */
	protected $knownSince;

	public function __construct() {
		$this->addType('mailboxId', 'integer');
		$this->addType('uid', 'integer');
		$this->addType('knownSince', 'integer');
	}
}
