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

/**
 * FIXME Auto-generated migration step: Please modify to your needs!
 */
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
			'length' => 4,
		]);
		$table->addColumn('owner', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('name', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);

		$table->setPrimaryKey(['id']);
		return $schema;
	}

}
