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

/**
 * Add master_user and master_user_separator columns to mail_provisionings table
 * for Dovecot Master User authentication support.
 *
 * @codeCoverageIgnore
 */
class Version5007Date20260124120000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$provisioningTable = $schema->getTable('mail_provisionings');
		if (!$provisioningTable->hasColumn('master_user')) {
			$provisioningTable->addColumn('master_user', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
		}
		if (!$provisioningTable->hasColumn('master_user_separator')) {
			$provisioningTable->addColumn('master_user_separator', Types::STRING, [
				'notnull' => false,
				'length' => 8,
			]);
		}

		return $schema;
	}
}
