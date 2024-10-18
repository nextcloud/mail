<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getOwner()
 * @method void setOwner(string $owner)
 * @method string geTitle()
 * @method void setTitle(string $title)
 * @method string getContent()
 * @method void setContent(string $content)
 */
class Snippet extends Entity implements JsonSerializable {
	protected $owner;
	protected $title;
	protected $content;

	public function __construct() {
		$this->addType('owner', 'string');
		$this->addType('title', 'string');
		$this->addType('content', 'string');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'owner' => $this->getOwner(),
			'title' => $this->geTitle(),
			'content' => $this->getContent(),
		];
	}
}
