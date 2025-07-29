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

		if (!$schema->hasTable('mail_context_chat_jobs')) {
			$table = $schema->createTable('mail_context_chat_jobs');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('account_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
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
			$table->addUniqueIndex(['user_id', 'account_id', 'mailbox_id'], 'mail_context_chat_jobs_uniq');
		}

		return $schema;
	}


}
