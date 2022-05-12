<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1130Date20220412111833 extends SimpleMigrationStep {
	/** @var IDBConnection $connection */
	private $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Truncate all tables that will get a new index later
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('mail_recipients')
			->where($qb->expr()->isNull('local_message_id'));
		$qb->execute();

		// Truncate and change primary key type for messages table
		$qb1 = $this->connection->getQueryBuilder();
		$qb1->delete('mail_messages');
		$qb1->execute();

		// Truncate message_tags table
		$qb2 = $this->connection->getQueryBuilder();
		$qb2->delete('mail_message_tags');
		$qb2->execute();

		// unset all locks for the mailboxes table
		$qb3 = $this->connection->getQueryBuilder();
		$qb3->update('mail_mailboxes')
			->set('sync_new_lock', $qb3->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_changed_lock', $qb3->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_vanished_lock', $qb3->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_new_token', $qb3->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_changed_token', $qb3->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_vanished_token', $qb3->createNamedParameter(null, IQueryBuilder::PARAM_NULL));
		$qb3->execute();
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// bigint and primary key with autoincrement is not possible on sqlite: https://github.com/nextcloud/server/commit/f57e334f8395e3b5c046b6d28480d798453e4866
		$isSqlite = $this->connection->getDatabasePlatform() instanceof SqlitePlatform;

		// Remove old unnamed attachments FK
		$attachmentsTable = $schema->getTable('mail_attachments');
		$fks = $attachmentsTable->getForeignKeys();
		foreach ($fks as $fk) {
			$attachmentsTable->removeForeignKey($fk->getName());
		}

		$recipientsTable = $schema->getTable('mail_recipients');
		$fks = $recipientsTable->getForeignKeys();
		foreach ($fks as $fk) {
			$recipientsTable->removeForeignKey($fk->getName());
		}

		if (!$isSqlite) {
			// Change primary column to bigint
			$recipientsTable->changeColumn('id', [
				'type' => Type::getType(Types::BIGINT),
				'length' => 20,
			]);
		}

		// Add new named FKs to attachments and recipients
		$recipientsTable->addForeignKeyConstraint($schema->getTable('mail_local_messages'), ['local_message_id'], ['id'], ['onDelete' => 'CASCADE'], 'recipient_local_message');
		$attachmentsTable->addForeignKeyConstraint($schema->getTable('mail_local_messages'), ['local_message_id'], ['id'], ['onDelete' => 'CASCADE'], 'attachment_local_message');

		$messagesTable = $schema->getTable('mail_messages');

		if (!$isSqlite) {
			// Change primary column to bigint
			$messagesTable->changeColumn('id', [
				'type' => Type::getType(Types::BIGINT),
				'length' => 20,
			]);
		}

		// Since we have instances where these indices were set manually, remove them first if they exist
		$indices = $messagesTable->getIndexes();
		foreach ($indices as $index) {
			if (!$index->isPrimary()) {
				$messagesTable->dropIndex($index->getName());
			}
		}

		// Add named indices
		$messagesTable->addIndex(['mailbox_id', 'flag_important', 'flag_deleted', 'flag_seen'], 'mail_messages_id_flags');
		$messagesTable->addIndex(['mailbox_id', 'flag_deleted', 'flag_flagged'], 'mail_messages_id_flags2');
		$messagesTable->addIndex(['mailbox_id'], 'mail_messages_mailbox_id');

		// Postgres doesn't allow for shortened indices, so let's skip the last index.
		if ($schema->getDatabasePlatform() instanceof PostgreSQL94Platform) {
			return $schema;
		}

		$messagesTable->addIndex(['mailbox_id', 'thread_root_id', 'sent_at'], 'mail_msg_thrd_root_snt_idx', [], ['lengths' => [null, 64, null]]);

		return $schema;
	}
}
