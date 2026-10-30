<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Contracts;

use OCA\Mail\Db\TrustedSender;
use OCA\Mail\Exception\InvalidAddressException;

interface ITrustedSenderService {
	public function isTrusted(string $uid, string $email): bool;

	/**
	 * @throws InvalidAddressException if the $email to trust or its $type is invalid
	 */
	public function trust(string $uid, string $email, string $type, ?bool $trust = true);

	/**
	 * @param string $uid
	 * @return TrustedSender[]
	 */
	public function getTrusted(string $uid): array;
}
