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

	// Sieve
	case SieveAccountUpdated = 'mail.sieve_account.updated';
	case SieveScriptUpdated = 'mail.sieve_script.updated';

	// S/MIME
	case SmimeCertificateCreated = 'mail.smime_certificate.created';
	case SmimeCertificateDeleted = 'mail.smime_certificate.deleted';

	// Text blocks
	case TextBlockShareCreated = 'mail.text_block_share.created';
	case TextBlockShareDeleted = 'mail.text_block_share.deleted';

	// Provisioning
	case ProvisioningConfigCreated = 'mail.provisioning_config.created';
	case ProvisioningConfigUpdated = 'mail.provisioning_config.updated';
	case ProvisioningConfigDeleted = 'mail.provisioning_config.deleted';
	case ProvisioningApplied = 'mail.provisioning.applied';

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
			self::SieveAccountUpdated => 'The Sieve server or credentials of a mail account were changed, or Sieve was turned on or off',
			self::SieveScriptUpdated => 'The active Sieve script of a mail account was replaced, including through mail filters and out-of-office replies',
			self::SmimeCertificateCreated => 'An S/MIME certificate was uploaded, optionally with its private key',
			self::SmimeCertificateDeleted => 'An S/MIME certificate was deleted with its private key',
			self::TextBlockShareCreated => 'A text block was shared with a user or a group',
			self::TextBlockShareDeleted => 'A text block was no longer shared with a user or a group',
			self::ProvisioningConfigCreated => 'An admin created a provisioning configuration that sets up mail accounts for matching users',
			self::ProvisioningConfigUpdated => 'An admin changed the servers or credentials of a provisioning configuration',
			self::ProvisioningConfigDeleted => 'An admin deleted a provisioning configuration together with all mail accounts provisioned from it',
			self::ProvisioningApplied => 'Mail accounts were created or updated for all users from the provisioning configurations, from the admin settings, occ mail:account:provision or an app update',
		};
	}
}
