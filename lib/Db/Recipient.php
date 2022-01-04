<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
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
 *
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method int getMessageId()
 * @method void setMessageId(int $messageId)
 * @method int getType()
 * @method void setType(int $type)
 * @method int getMailboxType()
 * @method void setMailboxType(int $mailboxType)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getEmail()
 * @method void setEmail(string $email)
 */
class Recipient extends Entity implements JsonSerializable {
	public const TYPE_INBOX = 0;
	public const TYPE_OUTBOX = 1;

	public const TYPE_FROM = 0;
	public const TYPE_TO = 1;
	public const TYPE_CC = 2;
	public const TYPE_BCC = 3;

	protected $messageId;
	protected $type;
	protected $mailboxType;
	protected $label;
	protected $email;

	public function __construct() {
		$this->addType('messageId', 'integer');
		$this->addType('type', 'integer');
		$this->addType('mailboxType', 'integer');
	}

	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'messageId' => $this->getMessageId(),
			'type' => $this->getType(),
			'mailboxType' => $this->getMailboxType(),
			'label' => $this->getLabel(),
			'email' => $this->getEmail()
		];
	}
}
