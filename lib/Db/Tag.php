<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getDisplayName()
 * @method void setDisplayName(string $displayName)
 * @method string getImapLabel()
 * @method void setImapLabel(string $imapLabel)
 * @method string getColor()
 * @method void setColor(string $color)
 * @method bool|null getIsDefaultTag()
 * @method void setIsDefaultTag(bool $flag)
 */
class Tag extends Entity implements JsonSerializable {
	protected $userId;
	protected $displayName;
	protected $imapLabel;
	protected $color;
	protected $isDefaultTag;

	public const LABEL_IMPORTANT = '$label1';
	public const LABEL_WORK = '$label2';
	public const LABEL_PERSONAL = '$label3';
	public const LABEL_TODO = '$label4';
	public const LABEL_LATER = '$label5';

	public function __construct() {
		$this->addType('isDefaultTag', 'boolean');
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'userId' => $this->getUserId(),
			'displayName' => $this->getDisplayName(),
			'imapLabel' => $this->getImapLabel(),
			'color' => $this->getColor(),
			'isDefaultTag' => ($this->getIsDefaultTag() === true),
		];
	}
}
