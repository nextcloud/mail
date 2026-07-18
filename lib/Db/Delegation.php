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

	private ?string $displayName = null;

	public function __construct() {
		$this->addType('userId', 'string');
		$this->addType('accountId', 'integer');
	}

	public function getDisplayName(): ?string {
		return $this->displayName;
	}

	public function setDisplayName(?string $displayName): void {
		$this->displayName = $displayName;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'accountId' => $this->getAccountId(),
			'userId' => $this->getUserId(),
			'displayName' => $this->displayName ?? $this->getUserId(),
		];
	}
}
