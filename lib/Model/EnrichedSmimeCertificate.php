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
	public function __construct(
		private SmimeCertificate $certificate,
		private SmimeCertificateInfo $info
	) {
	}

	public function getCertificate(): SmimeCertificate {
		return $this->certificate;
	}

	public function setCertificate(SmimeCertificate $certificate): void {
		$this->certificate = $certificate;
	}

	public function getInfo(): SmimeCertificateInfo {
		return $this->info;
	}

	public function setInfo(SmimeCertificateInfo $info): void {
		$this->info = $info;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$json = $this->certificate->jsonSerialize();
		$json['info'] = $this->info->jsonSerialize();
		return $json;
	}
}
