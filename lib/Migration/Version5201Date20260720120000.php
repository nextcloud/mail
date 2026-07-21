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
use Override;

class Version5201Date20260720120000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_messages_imip')) {
			$table = $schema->createTable('mail_messages_imip');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('imip_message_id', Types::BIGINT, [
				'notnull' => true,
			]);
			$table->addColumn('error', Types::BOOLEAN, [
				'notnull' => true,
				'default' => false,
			]);
			$table->addColumn('processed_at', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['imip_message_id'], 'mail_msg_imip_msg_uniq');
			$table->addIndex(['error', 'processed_at'], 'mail_msg_imip_unproc_idx');

			if ($schema->hasTable('mail_messages')) {
				$table->addForeignKeyConstraint(
					$schema->getTable('mail_messages'),
					['imip_message_id'],
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
