<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\SMTP;

use Horde_Mail_Exception;
use Horde_Mail_Rfc822;
use Horde_Mail_Transport_Smtphorde;

class LenientSmtphordeTransport extends Horde_Mail_Transport_Smtphorde {
	/**
	 * Work around Horde's overly strict domain validation for envelope senders.
	 *
	 * @throws Horde_Mail_Exception
	 */
	public function prepareHeaders(array $headers) {
		$from = null;
		$lines = [];
		$raw = $headers['_raw'] ?? null;

		foreach ($headers as $key => $value) {
			if (strcasecmp($key, 'From') === 0) {
				$parser = new Horde_Mail_Rfc822();
				$addresses = $parser->parseAddressList($value, [
					'validate' => false,
				]);
				$from = $addresses[0]->bare_address;

				if (strstr($from, ' ')) {
					return false;
				}

				$lines[] = $key . ': ' . $this->_normalizeEOL($value);
			} elseif (!$raw && (strcasecmp($key, 'Received') === 0)) {
				$received = [];
				if (!is_array($value)) {
					$value = [$value];
				}

				foreach ($value as $line) {
					$received[] = $key . ': ' . $this->_normalizeEOL($line);
				}

				$lines = array_merge($received, $lines);
			} elseif (!$raw) {
				if (is_array($value)) {
					$value = implode(', ', $value);
				}
				$lines[] = $key . ': ' . $this->_normalizeEOL($value);
			}
		}

		return [$from, $raw ?: implode($this->sep, $lines)];
	}
}
