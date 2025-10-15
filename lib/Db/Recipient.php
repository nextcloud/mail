<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	#[\Override]
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
