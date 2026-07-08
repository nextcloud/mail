<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string getBody()
 * @method void setBody(string $body)
 */
class MessageTemplate extends Entity implements JsonSerializable {
	/** @var string */
	protected $userId;
	/** @var string */
	protected $title;
	/** @var string */
	protected $body;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('title', 'string');
		$this->addType('body', 'string');
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'userId' => $this->getUserId(),
			'title' => $this->getTitle(),
			'body' => $this->getBody()
		];
	}
}
