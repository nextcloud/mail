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
	public function __construct(
		private IConfig $config,
	) {
	}

	/**
	 * Whether IMAP/SMTP/Sieve debug logging is active for this account, either
	 * because it was enabled for the account itself or system-wide.
	 */
	public function isActive(Account $account): bool {
		return $account->getMailAccount()->getDebug() || $this->config->getSystemValueBool('app.mail.debug');
	}

	/**
	 * @return string|null the log file path, or null if debug logging is not active
	 */
	public function getPath(Account $account, string $protocol): ?string {
		return $this->isActive($account) ? $this->buildPath($account, $protocol) : null;
	}

	/**
	 * Build the log file path regardless of whether debug logging is currently active.
	 *
	 * The account might not have been persisted yet (e.g. during the connectivity
	 * check when setting up a new account), in which case its id is not known yet.
	 */
	public function buildPath(Account $account, string $protocol): string {
		$id = $account->getId() ?? 'new';
		$fn = "mail-{$account->getUserId()}-{$id}-{$protocol}.log";
		return $this->config->getSystemValue('datadirectory') . '/' . $fn;
	}
}
