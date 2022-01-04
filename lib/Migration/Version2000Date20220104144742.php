<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2000Date20220104144742 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$recipientsTable = $schema->getTable('mail_recipients');
		$recipientsTable->addColumn('mailbox_type', 'integer', [
			'notnull' => true,
			'default' => 0,
		]);

		$localMailboxTable = $schema->createTable('mail_local_mailbox');
		$localMailboxTable->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$localMailboxTable->addColumn('type', 'integer', [
			'notnull' => true,
			'unsigned' => true,
			'length' => 1,
		]);
		$localMailboxTable->addColumn('account_id', 'string', [
			'notnull' => true,
			'length' => 4,
		]);
		$localMailboxTable->addColumn('send_at', 'integer', [
			'notnull' => false,
			'unsigned' => true,
			'length' => 4
		]);
		$localMailboxTable->addColumn('subject', 'text', [
			'notnull' => true,
			'length' => 255
		]);
		$localMailboxTable->addColumn('body', 'text', [
			'notnull' => true,
			'length' => 16777215
		]);
		$localMailboxTable->addColumn('html', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$localMailboxTable->addColumn('mdn', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$localMailboxTable->addColumn('in_reply_to_message_id', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$localMailboxTable->setPrimaryKey(['id']);

		$attachmentsTable = $schema->createTable('mail_lcl_mbx_attchmts');
		$attachmentsTable->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$attachmentsTable->addColumn('local_message_id', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$attachmentsTable->addColumn('attachment_id', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$attachmentsTable->setPrimaryKey(['id']);

		return $schema;
	}
}
