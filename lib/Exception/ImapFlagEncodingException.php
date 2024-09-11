<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

use Exception;

class ImapFlagEncodingException extends Exception {
	public static function create($label): ImapFlagEncodingException {
		return new self(
			'Failed to convert the given label "' . $label . '" to UTF7-IMAP',
			0,
		);
	}
}
