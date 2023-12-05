<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
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
