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
 */
class MessageTags extends Entity implements JsonSerializable {
	protected $imapMessageId;
	protected $tagId;

	public function __construct() {
		$this->addType('tagId', 'integer');
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'imapMessageId' => $this->getImapMessageId(),
			'tagId' => $this->getTagId(),
		];
	}
}
