<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method void setAccountId(int $accountId)
 * @method int getAccountId()
 * @method void setName(string|null $name)
 * @method string|null getName()
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

	/** @var string|null */
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
		$this->addType('accountId', 'integer');
		$this->addType('name', 'string');
		$this->addType('alias', 'string');
		$this->addType('provisioningId', 'integer');
		$this->addType('signatureMode', 'integer');
		$this->addType('smimeCertificateId', 'integer');
	}

	public function isProvisioned(): bool {
		return $this->getProvisioningId() !== null;
	}

	#[\Override]
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
