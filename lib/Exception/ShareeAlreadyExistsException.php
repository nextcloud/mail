<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Exception;

class ShareeAlreadyExistsException extends Exception {
	public function __construct() {
		parent::__construct('Sharee already exists');
	}
}
