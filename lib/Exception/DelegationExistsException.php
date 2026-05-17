<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Exception;

class DelegationExistsException extends Exception {
	public function __construct(string $message = 'Delegation already exists') {
		parent::__construct($message);
	}
}
