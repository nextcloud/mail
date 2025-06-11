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

class Version3500Date20231115184458 extends SimpleMigrationStep {

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

		$indexOld = 'UNIQ_22DEBD839B6B5FBA5E237E06';
		$indexNew = 'mail_mb_account_id_name_hash';

		if ($mailboxesTable->hasIndex($indexOld)) {
			$mailboxesTable->dropIndex($indexOld);
		}

		if (!$mailboxesTable->hasIndex($indexNew)) {
			$mailboxesTable->addUniqueIndex(['account_id', 'name_hash'], $indexNew);
		}

		$nameHashColumn = $mailboxesTable->getColumn('name_hash');
		if (!$nameHashColumn->getNotnull()) {
			$nameHashColumn->setNotnull(true);
		}

		return $schema;
	}
}
