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
 * @method int getAccountId()
 * @method void setAccountId(int $accountId)
 */
class Actions extends Entity implements JsonSerializable {
	protected $name;
	protected $accountId;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('accountId', 'integer');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'accountId' => $this->getAccountId(),

		];
	}
}
