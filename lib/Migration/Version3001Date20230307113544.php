<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3001Date20230307113544 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$accountsTable = $schema->getTable('mail_accounts');
		$accountsTable->addColumn('junk_mailbox_id', 'integer', [
			'notnull' => false,
			'default' => null,
			'length' => 20,
		]);
		$accountsTable->addColumn('move_junk', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);

		return $schema;
	}
}
