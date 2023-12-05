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
use ReturnTypeWillChange;

/**
 * @method int|null getMessageId()
 * @method void setMessageId(int $messageId)
 * @method int|null getLocalMessageId()
 * @method void setLocalMessageId(int $localMessageId)
 * @method int getType()
 * @method void setType(int $type)
 * @method string getLabel()
 * @method void setLabel(string $label)
 * @method string getEmail()
 * @method void setEmail(string $email)
 */
class Recipient extends Entity implements JsonSerializable {
	public const TYPE_FROM = 0;
	public const TYPE_TO = 1;
	public const TYPE_CC = 2;
	public const TYPE_BCC = 3;

	/** @var int|null */
	protected $messageId;

	/**
	 * @var int
	 * @psalm-var self::TYPE_*
	 */
	protected $type;

	/** @var int|null */
	protected $localMessageId;

	/** @var string */
	protected $label;

	/** @var string */
	protected $email;

	public function __construct() {
		$this->addType('messageId', 'integer');
		$this->addType('localMessageId', 'integer');
		$this->addType('type', 'integer');
		$this->addType('mailboxType', 'integer');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'messageId' => $this->getMessageId(),
			'localMessageId' => $this->getLocalMessageId(),
			'type' => $this->getType(),
			'label' => $this->getLabel(),
			'email' => $this->getEmail()
		];
	}
}
