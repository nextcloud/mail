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
 * @method setAddress(string $address): void
 * @method getAddress(): string
 * @method setUserId(string $userId): void
 * @method getUserId(): string
 * @method setType(string $type): void
 * @method getType(): string
 */
class InternalAddress extends Entity implements JsonSerializable {

	protected $address ;
	protected $userId;
	protected $type;

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'address' => $this->address,
			'uid' => $this->userId,
			'type' => $this->type,
		];
	}
}
