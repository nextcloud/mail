<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

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
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->createTable('mail_messages');
		$messagesTable->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$messagesTable->addColumn('uid', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('message_id', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$messagesTable->addColumn('mailbox_id', 'string', [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('subject', 'string', [
			'notnull' => true,
			'length' => 255,
			'default' => '',
		]);
		$messagesTable->addColumn('sent_at', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('flag_answered', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_deleted', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_draft', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_flagged', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_seen', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_forwarded', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_junk', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_notjunk', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('updated_at', 'integer', [
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
		$recipientsTable->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$recipientsTable->addColumn('message_id', 'integer', [
			'notnull' => true,
			'length' => 20,
		]);
		$recipientsTable->addColumn('type', 'integer', [
			'notnull' => true,
			'length' => 2,
		]);
		$recipientsTable->addColumn('label', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$recipientsTable->addColumn('email', 'string', [
			'notnull' => true,
			'length' => 255,
		]);
		$recipientsTable->setPrimaryKey(['id']);
		$recipientsTable->addIndex(['message_id'], 'mail_recipient_msg_id_idx');
		$recipientsTable->addIndex(['email'], 'mail_recipient_email_idx');

		$mailboxTable = $schema->getTable('mail_mailboxes');
		$mailboxTable->addColumn('sync_new_lock', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_changed_lock', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_vanished_lock', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_new_token', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$mailboxTable->addColumn('sync_changed_token', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$mailboxTable->addColumn('sync_vanished_token', 'string', [
			'notnull' => false,
			'length' => 255,
		]);

		return $schema;
	}
}
