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

class Version3300Date20230801124717 extends SimpleMigrationStep {

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
		if (!$accountsTable->hasColumn('trash_retention_days')) {
			$accountsTable->addColumn('trash_retention_days', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
			]);
		}

		if (!$schema->hasTable('mail_messages_retention')) {
			$messagesRetentionTable = $schema->createTable('mail_messages_retention');
			$messagesRetentionTable->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$messagesRetentionTable->addColumn('message_id', Types::STRING, [
				'notnull' => true,
				'length' => 1024,
			]);
			$messagesRetentionTable->addColumn('known_since', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$messagesRetentionTable->setPrimaryKey(['id'], 'mail_msg_retention_id_idx');
		}

		return $schema;
	}
}
