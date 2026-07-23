<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Exception\InvalidOauthStateException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Security\ICrypto;

class OauthStateService {
	public const TTL = 600;

	public function __construct(
		private ICrypto $crypto,
		private ITimeFactory $time,
	) {
	}

	public function createState(int $accountId, string $userId): string {
		$timestamp = $this->time->getTime();
		$hmac = bin2hex($this->crypto->calculateHMAC("$accountId.$userId.$timestamp"));
		return "$accountId.$timestamp.$hmac";
	}

	/**
	 * @throws InvalidOauthStateException
	 */
	public function validateAndConsume(string $state, string $userId): int {
		$parts = explode('.', $state, 3);
		if (count($parts) !== 3) {
			throw new InvalidOauthStateException('Malformed OAuth state');
		}
		[$accountId, $timestamp, $hmac] = $parts;

		$expected = bin2hex($this->crypto->calculateHMAC("$accountId.$userId.$timestamp"));
		if (!hash_equals($expected, $hmac)) {
			throw new InvalidOauthStateException('OAuth state HMAC mismatch');
		}

		if (($this->time->getTime() - (int)$timestamp) > self::TTL) {
			throw new InvalidOauthStateException('OAuth state expired');
		}

		return (int)$accountId;
	}
}
