<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2300Date20230127093733 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$outboxTable = $schema->getTable('mail_local_messages');
		if (!$outboxTable->hasColumn('smime_sign')) {
			$outboxTable->addColumn('smime_sign', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}
		if (!$outboxTable->hasColumn('smime_certificate_id')) {
			$outboxTable->addColumn('smime_certificate_id', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
			]);
		}

		$accountsTable = $schema->getTable('mail_accounts');
		if (!$accountsTable->hasColumn('smime_certificate_id')) {
			$accountsTable->addColumn('smime_certificate_id', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
			]);
		}

		$aliasesTable = $schema->getTable('mail_aliases');
		if (!$aliasesTable->hasColumn('smime_certificate_id')) {
			$aliasesTable->addColumn('smime_certificate_id', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
			]);
		}

		return $schema;
	}
}
