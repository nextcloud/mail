<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1120Date20220223094709 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$localMessageTable = $schema->createTable('mail_local_messages');
		$localMessageTable->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$localMessageTable->addColumn('type', Types::INTEGER, [
			'notnull' => true,
			'unsigned' => true,
			'length' => 1,
		]);
		$localMessageTable->addColumn('account_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$localMessageTable->addColumn('alias_id', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$localMessageTable->addColumn('send_at', Types::INTEGER, [
			'notnull' => false,
			'length' => 4
		]);
		$localMessageTable->addColumn('subject', Types::TEXT, [
			'notnull' => true,
			'length' => 255
		]);
		$localMessageTable->addColumn('body', Types::TEXT, [
			'notnull' => true
		]);
		$localMessageTable->addColumn('html', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$localMessageTable->addColumn('in_reply_to_message_id', Types::TEXT, [
			'notnull' => false,
			'length' => 1023,
		]);
		$localMessageTable->setPrimaryKey(['id']);

		/** @var Table $recipientsTable */
		$recipientsTable = $schema->getTable('mail_recipients');
		$recipientsTable->addColumn('local_message_id', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$recipientsTable->changeColumn('message_id', [
			'notnull' => false
		]);
		$recipientsTable->addForeignKeyConstraint($localMessageTable, ['local_message_id'], ['id'], ['onDelete' => 'CASCADE']);

		$attachmentsTable = $schema->getTable('mail_attachments');
		$attachmentsTable->addColumn('local_message_id', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$attachmentsTable->addForeignKeyConstraint($localMessageTable, ['local_message_id'], ['id'], ['onDelete' => 'CASCADE']);

		return $schema;
	}
}
