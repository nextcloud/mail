<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3400Date20230807300513 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$accountsTable = $schema->getTable('mail_accounts');
		if (!$accountsTable->hasColumn('snooze_mailbox_id')) {
			$accountsTable->addColumn('snooze_mailbox_id', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
				'length' => 20,
			]);
		}

		if (!$schema->hasTable('mail_messages_snoozed')) {
			$messagesSnoozedTable = $schema->createTable('mail_messages_snoozed');
			$messagesSnoozedTable->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$messagesSnoozedTable->addColumn('message_id', Types::STRING, [
				'notnull' => true,
				'length' => 1024,
			]);
			$messagesSnoozedTable->addColumn('snoozed_until', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$messagesSnoozedTable->setPrimaryKey(['id'], 'mail_msg_snoozed_id_idx');
		}

		return $schema;
	}
}
