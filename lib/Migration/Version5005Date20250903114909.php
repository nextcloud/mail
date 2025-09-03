<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version5005Date20250903114909 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('mail_action_step')) {
			return null;
		}
		$table = $schema->createTable('mail_action_step');

		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('name', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('order', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('action_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('mailbox_id', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$table->addColumn('tag_id', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);

		$table->setPrimaryKey(['id']);
		if ($schema->hasTable('mail_actions')) {
			$table->addForeignKeyConstraint(
				$schema->getTable('mail_actions'),
				['action_id'],
				['id'],
				[
					'onDelete' => 'CASCADE',
				]
			);
		}
		if ($schema->hasTable('mail_mailboxes')) {
			$table->addForeignKeyConstraint(
				$schema->getTable('mail_mailboxes'),
				['mailbox_id'],
				['id'],
				[
					'onDelete' => 'SET NULL',
				]
			);
		}
		if ($schema->hasTable('mail_tags')) {
			$table->addForeignKeyConstraint(
				$schema->getTable('mail_tags'),
				['tag_id'],
				['id'],
				[
					'onDelete' => 'SET NULL',
				]
			);
		}
		return $schema;
	}

}
