<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Util;

use OCP\ServerVersion as OCPServerVersion;

class ServerVersion {

	public function __construct(
		private OCPServerVersion $serverVersion,
	) {
	}

	public function getMajorVersion(): int {
		return $this->serverVersion->getMajorVersion();
	}

}
