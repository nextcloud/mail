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
	private ?string $commonName;
	private ?string $emailAddress;
	private int $notAfter;
	private SmimeCertificatePurposes $purposes;
	private bool $isChainVerified;

	public function __construct(?string $commonName,
		?string $emailAddress,
		int $notAfter,
		SmimeCertificatePurposes $purposes,
		bool $isChainVerified) {
		$this->commonName = $commonName;
		$this->emailAddress = $emailAddress;
		$this->notAfter = $notAfter;
		$this->purposes = $purposes;
		$this->isChainVerified = $isChainVerified;
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

	/**
	 * @return int
	 */
	public function getNotAfter(): int {
		return $this->notAfter;
	}

	/**
	 * @param int $notAfter
	 */
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
