<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Command;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;

/** Read two newline-delimited credentials without passing them as process arguments. */
final class StdinImapPasswords {
	private const MAX_PASSWORD_LENGTH = 4096;

	/** @return array{string, string} */
	public static function read(InputInterface $input): array {
		$stream = $input instanceof StreamableInputInterface ? $input->getStream() : null;
		$stream ??= STDIN;
		$passwords = [];
		foreach (['IMAP', 'SMTP'] as $protocol) {
			$line = fgets($stream, self::MAX_PASSWORD_LENGTH + 3);
			if ($line === false || !str_ends_with($line, "\n")) {
				throw new InvalidArgumentException("Missing or oversized $protocol password on stdin");
			}
			$password = substr($line, 0, -1);
			if (str_ends_with($password, "\r")) {
				$password = substr($password, 0, -1);
			}
			if ($password === '' || strlen($password) > self::MAX_PASSWORD_LENGTH || str_contains($password, "\0") || str_contains($password, "\r")) {
				throw new InvalidArgumentException("Invalid $protocol password on stdin");
			}
			$passwords[] = $password;
		}
		return $passwords;
	}
}
