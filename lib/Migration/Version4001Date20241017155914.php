<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @codeCoverageIgnore
 */
class Version4001Date20241017155914 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('mail_blocks_shares')) {
			return null;
		}
		$table = $schema->createTable('mail_blocks_shares');

		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('type', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('share_with', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('text_block_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['text_block_id', 'share_with'], 'mail_blocks_shares_tbid_sw_idx');
		if ($schema->hasTable('mail_text_blocks')) {
			$table->addForeignKeyConstraint(
				$schema->getTable('mail_text_blocks'),
				['text_block_id'],
				['id'],
				[
					'onDelete' => 'CASCADE',
				]
			);
		}
		return $schema;
	}
}
