<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1040Date20200422142920 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
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
		$messagesTable->addColumn('mailbox_id', 'integer', [
			'notnull' => true,
			'length' => 20,
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
		$messagesTable->addColumn('flag_attachments', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_important', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('structure_analyzed', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('preview_text', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$messagesTable->addColumn('updated_at', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$messagesTable->setPrimaryKey(['id']);
		// We allow each UID just once
		$messagesTable->addUniqueIndex(
			[
				'uid',
				'mailbox_id',
			],
			'mail_msg_mb_uid_idx'
		);
		$messagesTable->addIndex(['sent_at'], 'mail_msg_sent_idx');

		return $schema;
	}
}
