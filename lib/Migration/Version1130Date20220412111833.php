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
use OCP\ITempManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version1130Date20220412111833 extends SimpleMigrationStep {
	private IDBConnection $connection;
	private LoggerInterface $logger;
	private array $recipients = [];
	private string $backupPath;

	public function __construct(IDBConnection $connection, LoggerInterface $logger, ITempManager $tempManager) {
		$this->connection = $connection;
		$this->logger = $logger;

		$tempBaseDir = $tempManager->getTempBaseDir();
		$this->backupPath = tempnam($tempBaseDir, 'mail_recipients_backup');
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Keep recipients backup
		$qb1 = $this->connection->getQueryBuilder();
		$qb1->select('local_message_id', 'message_id', 'type', 'label', 'email')
			->from('mail_recipients')
			->where($qb1->expr()->isNotNull('local_message_id'));

		$result = $qb1->executeQuery();
		$this->recipients = $result->fetchAll();
		$result->closeCursor();

		$backupFile = fopen($this->backupPath, 'rb+');
		if (is_resource($backupFile)) {
			$this->logger->warning(
				'Migration Version1130Date20220412111833 is going to truncate the mail_recipients table. '
				. 'We made a backup of the outbox recipients and try to restore them later. When the migration fails restore the data manually. '
				. 'Path to backup file: ' . $this->backupPath
			);

			fputcsv($backupFile, ['local_message_id', 'message_id', 'type', 'label', 'email']);
			foreach ($this->recipients as $recipient) {
				fputcsv($backupFile, array_values($recipient));
			}
			fclose($backupFile);
		}

		// Truncate recipients table
		$sql1 = $this->connection->getDatabasePlatform()->getTruncateTableSQL('`*PREFIX*mail_recipients`', false);
		$this->connection->executeStatement($sql1);

		// Truncate and change primary key type for messages table
		$sql2 = $this->connection->getDatabasePlatform()->getTruncateTableSQL('`*PREFIX*mail_messages`', false);
		$this->connection->executeStatement($sql2);

		// Truncate message_tags table
		$sql3 = $this->connection->getDatabasePlatform()->getTruncateTableSQL('`*PREFIX*mail_message_tags`', false);
		$this->connection->executeStatement($sql3);

		// unset all locks for the mailboxes table
		$qb2 = $this->connection->getQueryBuilder();
		$qb2->update('mail_mailboxes')
			->set('sync_new_lock', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_changed_lock', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_vanished_lock', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_new_token', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_changed_token', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->set('sync_vanished_token', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL));
		$qb2->executeStatement();
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
		if ($this->connection->getDatabasePlatform() instanceof PostgreSQL94Platform) {
			return $schema;
		}

		$messagesTable->addIndex(['mailbox_id', 'thread_root_id', 'sent_at'], 'mail_msg_thrd_root_snt_idx', [], ['lengths' => [null, 64, null]]);

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb1 = $this->connection->getQueryBuilder();
		$qb1->insert('mail_recipients')
			->values([
				'local_message_id' => $qb1->createParameter('local_message_id'),
				'message_id' => $qb1->createParameter('message_id'),
				'type' => $qb1->createParameter('type'),
				'label' => $qb1->createParameter('label'),
				'email' => $qb1->createParameter('email'),
			]);

		foreach ($this->recipients as $recipient) {
			$qb1->setParameters($recipient);
			$qb1->executeStatement();
		}

		if (is_file($this->backupPath)) {
			$this->logger->warning('Migration Version1130Date20220412111833 completed. Going to delete backup file: ' . $this->backupPath);
			@unlink($this->backupPath);
		}
	}
}
