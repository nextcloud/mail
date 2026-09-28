<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

/**
 * Option names and accepted values of the provisioning configuration commands.
 *
 * Kept out of {@see ProvisioningOptions} because traits cannot declare
 * constants before PHP 8.2.
 */
final class ProvisioningOption {
	public const SSL_MODES = ['none', 'ssl', 'tls'];

	public const MASTER_PASSWORD = 'master-password';
	public const NO_MASTER_PASSWORD = 'no-master-password';
	public const NO_SIEVE = 'no-sieve';
	public const NO_LDAP_ALIASES = 'no-ldap-aliases';

	/** Option name => [data key, description] */
	public const FIELDS = [
		'provisioning-domain' => ['provisioningDomain', 'Email domain to provision, or * for all users'],
		'email-template' => ['emailTemplate', 'Account email, supports %USERID%, %EMAIL% and %LDAP:attribute%'],
		'imap-user' => ['imapUser', 'IMAP login, supports the same placeholders as the email template'],
		'imap-host' => ['imapHost', 'IMAP host'],
		'imap-port' => ['imapPort', 'IMAP port'],
		'imap-ssl-mode' => ['imapSslMode', 'IMAP encryption: none, ssl or tls'],
		'smtp-user' => ['smtpUser', 'SMTP login, supports the same placeholders as the email template'],
		'smtp-host' => ['smtpHost', 'SMTP host'],
		'smtp-port' => ['smtpPort', 'SMTP port'],
		'smtp-ssl-mode' => ['smtpSslMode', 'SMTP encryption: none, ssl or tls'],
		'sieve-user' => ['sieveUser', 'Sieve login, supports the same placeholders as the email template'],
		'sieve-host' => ['sieveHost', 'Sieve host, enables Sieve when set'],
		'sieve-port' => ['sievePort', 'Sieve port, required when Sieve is enabled'],
		'sieve-ssl-mode' => ['sieveSslMode', 'Sieve encryption: none, ssl or tls'],
		'master-user' => ['masterUser', 'Master user suffix appended to the login, e.g. *masteruser'],
		'ldap-aliases-attribute' => ['ldapAliasesAttribute', 'LDAP attribute to read aliases from, enables alias provisioning when set'],
	];
}
