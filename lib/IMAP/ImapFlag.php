<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use OCA\Mail\Exception\ImapFlagEncodingException;

class ImapFlag {
	/**
	 * @throws ImapFlagEncodingException
	 */
	public function create(string $label): string {
		$flag = str_replace(' ', '_', $label);
		$flag = mb_convert_encoding($flag, 'UTF7-IMAP', 'UTF-8');

		if ($flag === false) {
			throw ImapFlagEncodingException::create($label);
		}

		return '$' . strtolower(mb_strcut($flag, 0, 63));
	}
}
