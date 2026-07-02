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
#[AddColumn(table: 'mail_accounts', name: 'protocol', type: ColumnType::STRING)]
#[AddColumn(table: 'mail_accounts', name: 'path', type: ColumnType::STRING)]
class Version5800Date20260401000001 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		$accountsTable = $schema->getTable('mail_accounts');
		if (!$accountsTable->hasColumn('protocol')) {
			$accountsTable->addColumn('protocol', Types::STRING, [
				'length' => 16,
				'default' => 'imap',
				'notnull' => true,
			]);
		}
		if (!$accountsTable->hasColumn('path')) {
			$accountsTable->addColumn('path', Types::STRING, [
				'length' => 512,
				'notnull' => false,
				'default' => null,
			]);
		}
		return $schema;
	}
}
