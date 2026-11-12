<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Support;

use OCA\Mail\Account;
use OCP\IConfig;

class DebugLogPathFactory {
	public const PROTOCOL_IMAP = 'imap';
	public const PROTOCOL_SMTP = 'smtp';
	public const PROTOCOL_SIEVE = 'sieve';

	public function __construct(
		private IConfig $config,
	) {
	}

	public function isActive(Account $account): bool {
		return $account->getMailAccount()->getDebug() || $this->config->getSystemValueBool('app.mail.debug');
	}

	/**
	 * @param self::PROTOCOL_* $protocol
	 */
	public function getPath(Account $account, string $protocol): ?string {
		return $this->isActive($account) ? $this->buildPath($account, $protocol) : null;
	}

	/**
	 * @param self::PROTOCOL_* $protocol
	 */
	public function buildPath(Account $account, string $protocol): string {
		$id = $account->getId() ?? 'new';
		$fn = "mail-{$account->getUserId()}-{$id}-{$protocol}.log";
		return $this->config->getSystemValueString('datadirectory') . '/' . $fn;
	}
}
