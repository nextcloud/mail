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

class Version5200Date20250728000000 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_cc_tasks')) {
			$table = $schema->createTable('mail_cc_tasks');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('mailbox_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->addColumn('last_message_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 20,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['mailbox_id'], 'mail_cc_tasks_uniq');
			if ($schema->hasTable('mail_mailboxes')) {
				$table->addForeignKeyConstraint(
					$schema->getTable('mail_mailboxes'),
					['mailbox_id'],
					['id'],
					[
						'onDelete' => 'CASCADE',
					]
				);
			}
		}

		return $schema;
	}


}
