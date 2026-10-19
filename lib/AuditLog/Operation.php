<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\AuditLog;

/**
 * Operations Mail writes to the admin audit log, in the form `app.entity.action`
 *
 * The values are matched by log consumers (filters, alert rules, SIEM imports),
 * so changing one is a breaking change for them. Case names may be renamed.
 */
enum Operation: string {
	// Accounts
	case AccountCreated = 'mail.account.created';
	case AccountUpdated = 'mail.account.updated';
	case AccountDeleted = 'mail.account.deleted';
	case DelegationCreated = 'mail.delegation.created';
	case DelegationDeleted = 'mail.delegation.deleted';

	/**
	 * Short description of when the operation is logged, used for the documentation
	 */
	public function description(): string {
		return match ($this) {
			self::AccountCreated => 'A mail account was set up with its IMAP and SMTP credentials',
			self::AccountUpdated => 'The servers or credentials of a mail account were changed',
			self::AccountDeleted => 'A mail account was deleted with its aliases and credentials',
			self::DelegationCreated => 'Another user was allowed to act on behalf of the owner of a mail account',
			self::DelegationDeleted => 'A user was no longer allowed to act on behalf of the owner of a mail account',
		};
	}
}
