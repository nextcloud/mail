<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1020Date20191002091035 extends SimpleMigrationStep {
	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->createTable('mail_messages');
		$messagesTable->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$messagesTable->addColumn('uid', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('message_id', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$messagesTable->addColumn('mailbox_id', Types::STRING, [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('subject', Types::STRING, [
			'notnull' => true,
			'length' => 255,
			'default' => '',
		]);
		$messagesTable->addColumn('sent_at', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('flag_answered', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_deleted', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_draft', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_flagged', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_seen', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_forwarded', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_junk', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_notjunk', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('updated_at', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$messagesTable->setPrimaryKey(['id']);
		// We allow each UID just once
		$messagesTable->addUniqueIndex([
			'uid',
			'mailbox_id',
		]);
		$messagesTable->addIndex(['sent_at'], 'mail_message_sent_idx');

		$recipientsTable = $schema->createTable('mail_recipients');
		$recipientsTable->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$recipientsTable->addColumn('message_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 20,
		]);
		$recipientsTable->addColumn('type', Types::INTEGER, [
			'notnull' => true,
			'length' => 2,
		]);
		$recipientsTable->addColumn('label', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$recipientsTable->addColumn('email', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$recipientsTable->setPrimaryKey(['id']);
		$recipientsTable->addIndex(['message_id'], 'mail_recipient_msg_id_idx');
		$recipientsTable->addIndex(['email'], 'mail_recipient_email_idx');

		$mailboxTable = $schema->getTable('mail_mailboxes');
		$mailboxTable->addColumn('sync_new_lock', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_changed_lock', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_vanished_lock', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_new_token', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$mailboxTable->addColumn('sync_changed_token', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$mailboxTable->addColumn('sync_vanished_token', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);

		return $schema;
	}
}
