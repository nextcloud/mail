<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3400Date20230907103114 extends SimpleMigrationStep {

	private IDBConnection $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		foreach (['mail_messages_retention', 'mail_messages_snoozed'] as $tableName) {
			$qb = $this->connection->getQueryBuilder();
			$query = $qb->delete($tableName);
			$query->executeStatement();
		}
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$retentionTable = $schema->getTable('mail_messages_retention');
		if ($retentionTable->hasIndex('mail_msg_retention_msgid_idx')) {
			$retentionTable->dropColumn('mail_msg_retention_msgid_idx');
		}
		if ($retentionTable->hasColumn('message_id')) {
			$retentionTable->dropColumn('message_id');
		}
		if (!$retentionTable->hasColumn('mailbox_id')) {
			$retentionTable->addColumn('mailbox_id', Types::INTEGER, [
				'notnull' => true,
			]);
		}
		if (!$retentionTable->hasColumn('uid')) {
			$retentionTable->addColumn('uid', Types::INTEGER, [
				'notnull' => true,
			]);
		}
		if (!$retentionTable->hasIndex('mail_msg_retention_mbuid_idx')) {
			$retentionTable->addUniqueIndex(['mailbox_id', 'uid'], 'mail_msg_retention_mbuid_idx');
		}

		$snoozedTable = $schema->getTable('mail_messages_snoozed');
		if ($snoozedTable->hasIndex('mail_msg_snoozed_msgid_idx')) {
			$snoozedTable->dropColumn('mail_msg_snoozed_msgid_idx');
		}
		if ($snoozedTable->hasColumn('message_id')) {
			$snoozedTable->dropColumn('message_id');
		}
		if (!$snoozedTable->hasColumn('mailbox_id')) {
			$snoozedTable->addColumn('mailbox_id', Types::INTEGER, [
				'notnull' => true,
			]);
		}
		if (!$snoozedTable->hasColumn('uid')) {
			$snoozedTable->addColumn('uid', Types::INTEGER, [
				'notnull' => true,
			]);
		}
		if (!$snoozedTable->hasIndex('mail_msg_snoozed_mbuid_idx')) {
			$snoozedTable->addUniqueIndex(['mailbox_id', 'uid'], 'mail_msg_snoozed_mbuid_idx');
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
