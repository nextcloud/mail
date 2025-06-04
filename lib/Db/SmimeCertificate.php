<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method void setUserId(string $userId)
 * @method string getUserId()
 * @method void setEmailAddress(string $emailAddress)
 * @method string getEmailAddress()
 * @method void setCertificate(string $certificate)
 * @method string getCertificate()
 * @method void setPrivateKey(string|null $privateKey)
 * @method string|null getPrivateKey()
 */
class SmimeCertificate extends Entity implements JsonSerializable {
	/** @var string */
	protected $userId;

	/** @var string */
	protected $emailAddress;

	/** @var string */
	protected $certificate;

	/** @var string|null */
	protected $privateKey;

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		// Don't leak certificate and private key
		return [
			'id' => $this->getId(),
			'emailAddress' => $this->getEmailAddress(),
			'hasKey' => $this->getPrivateKey() !== null,
		];
	}
}
