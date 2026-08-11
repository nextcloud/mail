<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

class TrashMailboxNotSetException extends ClientException {
	public function __construct() {
		parent::__construct('No trash mailbox configured');
	}
}
