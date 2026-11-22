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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3400Date20230907103114 extends SimpleMigrationStep {

	private IDBConnection $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	#[\Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		foreach (['mail_messages_retention', 'mail_messages_snoozed'] as $tableName) {
			$qb = $this->connection->getQueryBuilder();
			$query = $qb->delete($tableName);
			$query->executeStatement();
		}
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
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

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
