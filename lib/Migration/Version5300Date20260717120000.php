<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Create the table holding admin-configured OIDC providers used for XOAUTH2
 * authentication of individual mail accounts (matched by the user's email domain).
 *
 * @psalm-api
 */
class Version5300Date20260717120000 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_oidc_providers')) {
			$table = $schema->createTable('mail_oidc_providers');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('email_domain', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('imap_host', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('imap_port', Types::SMALLINT, [
				'notnull' => true,
				'unsigned' => true,
				'default' => 993,
			]);
			$table->addColumn('imap_ssl_mode', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => 'ssl',
			]);
			$table->addColumn('smtp_host', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('smtp_port', Types::SMALLINT, [
				'notnull' => true,
				'unsigned' => true,
				'default' => 587,
			]);
			$table->addColumn('smtp_ssl_mode', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => 'tls',
			]);
			$table->addColumn('client_id', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('client_secret', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('discovery_url', Types::STRING, [
				'notnull' => true,
				'length' => 2048,
				'default' => '',
			]);
			$table->addColumn('manual_endpoints', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
			$table->addColumn('authorization_endpoint', Types::STRING, [
				'notnull' => true,
				'length' => 2048,
				'default' => '',
			]);
			$table->addColumn('token_endpoint', Types::STRING, [
				'notnull' => true,
				'length' => 2048,
				'default' => '',
			]);
			$table->addColumn('introspection_endpoint', Types::STRING, [
				'notnull' => true,
				'length' => 2048,
				'default' => '',
			]);
			$table->addColumn('scope', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => 'openid email offline_access',
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['email_domain'], 'mail_oidc_dm_idx');
		}

		// Set when the stored OIDC grant can no longer be renewed (refresh token
		// expired/revoked, or none was ever issued) so the client can prompt the user
		// to re-authenticate instead of failing silently.
		$accounts = $schema->getTable('mail_accounts');
		if (!$accounts->hasColumn('oauth_needs_reauth')) {
			$accounts->addColumn('oauth_needs_reauth', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}

		return $schema;
	}
}
