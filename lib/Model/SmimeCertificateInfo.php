<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use JsonSerializable;
use ReturnTypeWillChange;

final class SmimeCertificateInfo implements JsonSerializable {
	public function __construct(
		private ?string $commonName,
		private ?string $emailAddress,
		private int $notAfter,
		private SmimeCertificatePurposes $purposes,
		private bool $isChainVerified
	) {
	}

	/**
	 * @return string
	 */
	public function getCommonName(): ?string {
		return $this->commonName;
	}

	/**
	 * @param string $commonName
	 */
	public function setCommonName(?string $commonName): void {
		$this->commonName = $commonName;
	}

	/**
	 * @return string
	 */
	public function getEmailAddress(): ?string {
		return $this->emailAddress;
	}

	/**
	 * @param string $emailAddress
	 */
	public function setEmailAddress(?string $emailAddress): void {
		$this->emailAddress = $emailAddress;
	}

	public function getNotAfter(): int {
		return $this->notAfter;
	}

	public function setNotAfter(int $notAfter): void {
		$this->notAfter = $notAfter;
	}

	public function getPurposes(): SmimeCertificatePurposes {
		return $this->purposes;
	}

	public function setPurposes(SmimeCertificatePurposes $purposes): void {
		$this->purposes = $purposes;
	}

	public function isChainVerified(): bool {
		return $this->isChainVerified;
	}

	public function setIsChainVerified(bool $isChainVerified): void {
		$this->isChainVerified = $isChainVerified;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'commonName' => $this->commonName,
			'emailAddress' => $this->emailAddress,
			'notAfter' => $this->notAfter,
			'purposes' => $this->purposes->jsonSerialize(),
			'isChainVerified' => $this->isChainVerified,
		];
	}
}
