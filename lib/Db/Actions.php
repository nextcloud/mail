<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method void setOwner(string $owner)
 * @method string getOwner()
 */
class Actions extends Entity implements JsonSerializable {
	protected $name;
	protected $owner;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('owner', 'string');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'owner' => $this->getOwner(),
		];
	}
}
