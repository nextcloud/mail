<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\Model;

use JsonSerializable;
use OCA\Mail\Db\SmimeCertificate;
use ReturnTypeWillChange;

class EnrichedSmimeCertificate implements JsonSerializable {
	private SmimeCertificate $certificate;
	private SmimeCertificateInfo $info;

	/**
	 * @param SmimeCertificate $certificate
	 * @param SmimeCertificateInfo $info
	 */
	public function __construct(SmimeCertificate $certificate, SmimeCertificateInfo $info) {
		$this->certificate = $certificate;
		$this->info = $info;
	}

	/**
	 * @return SmimeCertificate
	 */
	public function getCertificate(): SmimeCertificate {
		return $this->certificate;
	}

	/**
	 * @param SmimeCertificate $certificate
	 */
	public function setCertificate(SmimeCertificate $certificate): void {
		$this->certificate = $certificate;
	}

	/**
	 * @return SmimeCertificateInfo
	 */
	public function getInfo(): SmimeCertificateInfo {
		return $this->info;
	}

	/**
	 * @param SmimeCertificateInfo $info
	 */
	public function setInfo(SmimeCertificateInfo $info): void {
		$this->info = $info;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$json = $this->certificate->jsonSerialize();
		$json['info'] = $this->info->jsonSerialize();
		return $json;
	}
}
