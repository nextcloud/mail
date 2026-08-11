<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method setEmail(string $email): void
 * @method getEmail(): string
 * @method setUserId(string $userId): void
 * @method getUserId(): string
 * @method setType(string $type): void
 * @method getType(): string
 */
class TrustedSender extends Entity implements JsonSerializable {
	/** @var string */
	protected $email;

	/** @var string */
	protected $userId;

	/** @var string */
	protected $type;

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'email' => $this->email,
			'uid' => $this->userId,
			'type' => $this->type,
		];
	}
}
