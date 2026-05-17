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
 * @method int getAccountId()
 * @method void setAccountId(int $accountId)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 */
class Delegation extends Entity implements JsonSerializable {
	protected $accountId;
	protected $userId;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('accountId', 'integer');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'accountId' => $this->getAccountId(),
			'userId' => $this->getUserId(),
		];
	}
}
