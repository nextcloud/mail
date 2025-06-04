<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2100Date20221013143851 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$accountsTable = $schema->getTable('mail_accounts');
		if (!$accountsTable->hasColumn('oauth_refresh_token')) {
			$accountsTable->addColumn(
				'oauth_refresh_token',
				Types::STRING,
				[
					'notnull' => false,
					'length' => 1023, // Fits Microsoft and Google tokens
				],
			);
		}
		if (!$accountsTable->hasColumn('oauth_token_ttl')) {
			$accountsTable->addColumn(
				'oauth_token_ttl',
				Types::INTEGER,
				[
					'notnull' => false,
					'length' => 8,
				],
			);
		}

		return $schema;
	}
}
