<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version5005Date20250903114906 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('mail_actions')) {
			return null;
		}
		$table = $schema->createTable('mail_actions');

		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
		]);
		$table->addColumn('name', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('account_id', Types::INTEGER, [
			'notnull' => true,
		]);

		$table->setPrimaryKey(['id']);
		if ($schema->hasTable('mail_accounts')) {
			$table->addForeignKeyConstraint(
				$schema->getTable('mail_accounts'),
				['account_id'],
				['id'],
				[
					'onDelete' => 'CASCADE',
				]
			);
		}
		return $schema;
	}

}
