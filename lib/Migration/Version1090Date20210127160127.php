<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1090Date20210127160127 extends SimpleMigrationStep {
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

		$table = $schema->getTable('mail_accounts');
		$table->addColumn('sieve_enabled', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$table->addColumn('sieve_host', Types::STRING, [
			'notnull' => false,
			'length' => 64,
			'default' => null,
		]);
		$table->addColumn('sieve_port', Types::STRING, [
			'notnull' => false,
			'length' => 6,
			'default' => null,
		]);
		$table->addColumn('sieve_ssl_mode', Types::STRING, [
			'notnull' => false,
			'length' => 10,
			'default' => null,
		]);
		$table->addColumn('sieve_user', Types::STRING, [
			'notnull' => false,
			'length' => 64,
			'default' => null,
		]);
		$table->addColumn('sieve_password', Types::STRING, [
			'notnull' => false,
			'length' => 2048,
			'default' => null,
		]);

		return $schema;
	}
}
