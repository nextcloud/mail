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
class Version4001Date20241017154801 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('mail_text_blocks')) {
			return null;
		}
		$table = $schema->createTable('mail_text_blocks');

		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('owner', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('title', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('content', Types::TEXT, [
			'notnull' => true,
		]);
		$table->addColumn('preview', Types::TEXT, [
			'notnull' => true,
		]);
		$table->setPrimaryKey(['id']);
		$table->addIndex(['owner'], 'mail_text_blocks_owner_idx');
		$table->addIndex(['id', 'owner'], 'mail_text_blocks_id_owner_idx');

		return $schema;
	}
}
