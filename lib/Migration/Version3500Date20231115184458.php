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

		/**
		 * The migration "Version0161Date20190902103701" created a unique index for account_id and name without
		 * specifying a name. Unfortunately, this resulted in different index names depending on the table
		 * prefix, which means we now have to loop through the indexes to find the correct one.
		 *
		 * This migration is from 2023-11 and, by now, most people should already have it. Although it is not
		 * recommended to change migrations after they have been released, we are still updating this
		 * one for correctness as it was supposed to drop the index here.
		 *
		 * On newer versions, this will be a no-op, as creating the index
		 * in "Version0161Date20190902103701" is commented out.
		 *
		 * @see \OCA\Mail\Migration\Version0161Date20190902103701::changeSchema
		 */
		foreach ($mailboxesTable->getIndexes() as $index) {
			if ($index->isUnique() && $index->spansColumns(['account_id', 'name'])) {
				$mailboxesTable->dropIndex($index->getName());
			}
		}

		$indexNew = 'mail_mb_account_id_name_hash';

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
