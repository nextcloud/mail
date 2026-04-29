<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Html;

use OCP\IConfig;
use OCP\Security\ICrypto;

class ProxyHmacGenerator {

	public function __construct(
		private IConfig $config,
		private ICrypto $crypto,
	) {
	}

	public function generate(int $id, string $src): string {
		return bin2hex($this->crypto->calculateHMAC(
			$src,
			implode('|', [
				$this->config->getSystemValueString('secret'),
				$id,
			]),
		));
	}

}
