<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

class DraftsMailboxNotSetException extends ClientException {
	public function __construct() {
		parent::__construct('No drafts mailbox configured');
	}
}
