<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use JsonSerializable;
use OCA\Mail\Db\SmimeCertificate;
use ReturnTypeWillChange;

class EnrichedSmimeCertificate implements JsonSerializable {
	private SmimeCertificate $certificate;
	private ?SmimeCertificateInfo $info;
	private ?string $error;

	public function __construct(SmimeCertificate $certificate,
		?SmimeCertificateInfo $info,
		?string $error = null) {
		$this->certificate = $certificate;
		$this->info = $info;
		$this->error = $error;
	}

	public function getCertificate(): SmimeCertificate {
		return $this->certificate;
	}

	public function setCertificate(SmimeCertificate $certificate): void {
		$this->certificate = $certificate;
	}

	public function getInfo(): ?SmimeCertificateInfo {
		return $this->info;
	}

	public function setInfo(?SmimeCertificateInfo $info): void {
		$this->info = $info;
	}

	public function getError(): ?string {
		return $this->error;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$json = $this->certificate->jsonSerialize();
		$json['info'] = $this->info?->jsonSerialize();
		$json['error'] = $this->error;
		return $json;
	}
}
