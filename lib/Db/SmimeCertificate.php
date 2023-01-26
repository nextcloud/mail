<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
