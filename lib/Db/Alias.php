<?php

declare(strict_types=1);

/**
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * Mail
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
 * @method void setAccountId(int $accountId)
 * @method int getAccountId()
 * @method void setName(string $name)
 * @method string getName()
 * @method void setAlias(string $alias)
 * @method string getAlias()
 * @method void setSignature(string|null $signature)
 * @method string|null getSignature()
 * @method void setProvisioningId(int $provisioningId)
 * @method int|null getProvisioningId()
 * @method int getSignatureMode()
 * @method void setSignatureMode(int $signatureMode)
 * @method int|null getSmimeCertificateId()
 * @method void setSmimeCertificateId(int|null $smimeCertificateId)
 */
class Alias extends Entity implements JsonSerializable {
	public const SIGNATURE_MODE_PLAIN = MailAccount::SIGNATURE_MODE_PLAIN;
	public const SIGNATURE_MODE_HTML = MailAccount::SIGNATURE_MODE_HTML;

	/** @var int */
	protected $accountId;

	/** @var string */
	protected $name;

	/** @var string */
	protected $alias;

	/** @var string|null */
	protected $signature;

	/** @var int|null */
	protected $provisioningId;

	/** @var integer */
	protected $signatureMode;

	/** @var int|null */
	protected $smimeCertificateId;

	public function __construct() {
		$this->addType('accountId', 'int');
		$this->addType('name', 'string');
		$this->addType('alias', 'string');
		$this->addType('provisioningId', 'int');
		$this->addType('signatureMode', 'int');
		$this->addType('smimeCertificateId', 'int');
	}

	public function isProvisioned(): bool {
		return $this->getProvisioningId() !== null;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'alias' => $this->getAlias(),
			'signature' => $this->getSignature(),
			'provisioned' => $this->isProvisioned(),
			'signatureMode' => $this->getSignatureMode(),
			'smimeCertificateId' => $this->getSmimeCertificateId(),
		];
	}
}
