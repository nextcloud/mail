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

class Version2300Date20221216115727 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$tableName = 'mail_smime_certificates';
		if (!$schema->hasTable($tableName)) {
			$table = $schema->createTable($tableName);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('email_address', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('certificate', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('private_key', Types::TEXT, [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id'], 'mail_smime_certs_id_idx');
			$table->addIndex(['user_id'], 'mail_smime_certs_uid_idx');
			// Dropped in Version3600Date20240205180726
			// $table->addIndex(['id', 'user_id'], 'mail_smime_certs_id_uid_idx');
		}

		return $schema;
	}
}
