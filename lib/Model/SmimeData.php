<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use JsonSerializable;

final class SmimeData implements JsonSerializable {
	private bool $isSigned;
	private ?bool $signatureIsValid;
	private bool $isEncrypted;

	public function __construct() {
		$this->isSigned = false;
		$this->signatureIsValid = null;
		$this->isEncrypted = false;
	}

	/**
	 * @return bool
	 */
	public function isSigned(): bool {
		return $this->isSigned;
	}

	/**
	 * @param bool $isSigned
	 */
	public function setIsSigned(bool $isSigned): void {
		$this->isSigned = $isSigned;
	}

	/**
	 * @return bool|null
	 */
	public function getSignatureIsValid(): ?bool {
		return $this->signatureIsValid;
	}

	/**
	 * @param bool|null $signatureIsValid
	 */
	public function setSignatureIsValid(?bool $signatureIsValid): void {
		$this->signatureIsValid = $signatureIsValid;
	}

	/**
	 * @return bool
	 */
	public function isEncrypted(): bool {
		return $this->isEncrypted;
	}

	/**
	 * @param bool $isEncrypted
	 * @return void
	 */
	public function setIsEncrypted(bool $isEncrypted): void {
		$this->isEncrypted = $isEncrypted;
	}

	#[\Override]
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'isSigned' => $this->isSigned,
			'signatureIsValid' => $this->signatureIsValid,
			'isEncrypted' => $this->isEncrypted,
		];
	}
}
