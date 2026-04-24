<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @psalm-api
 */
#[AddColumn(table: 'mail_mailboxes', name: 'remote_parent_id', type: ColumnType::STRING)]
#[AddColumn(table: 'mail_mailboxes', name: 'remote_id', type: ColumnType::STRING)]
#[AddColumn(table: 'mail_mailboxes', name: 'state', type: ColumnType::STRING)]
class Version5800Date20260401000002 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		$mailboxesTable = $schema->getTable('mail_mailboxes');
		if (!$mailboxesTable->hasColumn('remote_parent_id')) {
			$mailboxesTable->addColumn('remote_parent_id', Types::STRING, [
				'length' => 255,
				'notnull' => false,
				'default' => null,
			]);
		}
		if (!$mailboxesTable->hasColumn('remote_id')) {
			$mailboxesTable->addColumn('remote_id', Types::STRING, [
				'length' => 255,
				'notnull' => false,
				'default' => null,
			]);
		}
		if (!$mailboxesTable->hasColumn('state')) {
			$mailboxesTable->addColumn('state', Types::STRING, [
				'length' => 64,
				'notnull' => false,
				'default' => null,
			]);
		}
		return $schema;
	}
}
