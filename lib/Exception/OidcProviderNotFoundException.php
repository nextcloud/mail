<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use OCP\AppFramework\Http;

class OidcProviderNotFoundException extends ClientException {
	#[\Override]
	public function getHttpCode(): int {
		return Http::STATUS_NOT_FOUND;
	}
}
