<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getImapMessageId()
 * @method void setImapMessageId(string $imapMessageId)
 * @method int getTagId()
 * @method void setTagId(int $tagId)
 * @method string getType()
 * @method void setType(string $type)
 */
class MessageTags extends Entity implements JsonSerializable {
	/**
	 * Tag was applied by the user (manual interaction).
	 */
	public const TYPE_USER = 'user';

	/**
	 * Tag was applied by the automatic importance classifier.
	 */
	public const TYPE_CLASSIFIER = 'classifier';

	protected $imapMessageId;
	protected $tagId;
	protected $type = self::TYPE_USER;

	public function __construct() {
		$this->addType('tagId', 'integer');
		$this->addType('type', 'string');
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'imapMessageId' => $this->getImapMessageId(),
			'tagId' => $this->getTagId(),
			'type' => $this->getType(),
		];
	}
}
