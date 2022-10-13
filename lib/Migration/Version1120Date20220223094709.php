<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1120Date20220223094709 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$localMessageTable = $schema->createTable('mail_local_messages');
		$localMessageTable->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$localMessageTable->addColumn('type', 'integer', [
			'notnull' => true,
			'unsigned' => true,
			'length' => 1,
		]);
		$localMessageTable->addColumn('account_id', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$localMessageTable->addColumn('alias_id', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$localMessageTable->addColumn('send_at', 'integer', [
			'notnull' => false,
			'length' => 4
		]);
		$localMessageTable->addColumn('subject', 'text', [
			'notnull' => true,
			'length' => 255
		]);
		$localMessageTable->addColumn('body', 'text', [
			'notnull' => true
		]);
		$localMessageTable->addColumn('html', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$localMessageTable->addColumn('in_reply_to_message_id', 'text', [
			'notnull' => false,
			'length' => 1023,
		]);
		$localMessageTable->setPrimaryKey(['id']);

		/** @var Table $recipientsTable */
		$recipientsTable = $schema->getTable('mail_recipients');
		$recipientsTable->addColumn('local_message_id', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$recipientsTable->changeColumn('message_id', [
			'notnull' => false
		]);
		$recipientsTable->addForeignKeyConstraint($localMessageTable, ['local_message_id'], ['id'], ['onDelete' => 'CASCADE']);

		$attachmentsTable = $schema->getTable('mail_attachments');
		$attachmentsTable->addColumn('local_message_id', 'integer', [
			'notnull' => false,
			'length' => 4,
		]);
		$attachmentsTable->addForeignKeyConstraint($localMessageTable, ['local_message_id'], ['id'], ['onDelete' => 'CASCADE']);

		return $schema;
	}
}
