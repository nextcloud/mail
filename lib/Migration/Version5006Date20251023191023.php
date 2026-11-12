<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version5006Date20251023191023 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$mailboxes = $schema->getTable('mail_mailboxes');

		/**
		 * The migration "Version0161Date20190902103701" created a unique index for account_id and name without
		 * specifying a name. Unfortunately, this resulted in different index names depending on the table
		 * prefix, which means we now have to loop through the indexes to find the correct one.
		 *
		 * The index on account_id and name were supposed to be dropped in "Version3500Date20231115184458",
		 * but this did not work on every setup due to the name mismatch caused by different table prefixes.
		 *
		 * On MySQL or MariaDB versions before 10.5, changing the length of the name column to 1024 fails if the
		 * index on account_id and name still exists, with the error "Specified key was too long; max key length is 3072 bytes."
		 * However, this change works on MariaDB 10.5 or newer.
		 *
		 * The reason is that MariaDB automatically converts a unique index using btree to hash if the key exceeds
		 * the maximum length and is supported by the storage engine: https://mariadb.com/docs/server/mariadb-quickstart-guides/mariadb-indexes-guide#unique-index
		 *
		 * This means that on setups with a different table prefix using MariaDB 10.5, the index on account_id and name
		 * might still exist. Since we don't need it, we will make another attempt to drop it here.
		 *
		 * @see \OCA\Mail\Migration\Version0161Date20190902103701::changeSchema
		 * @see \OCA\Mail\Migration\Version3500Date20231115184458::changeSchema
		 * @see \OCA\Mail\Migration\Version5006Date20250927130132::changeSchema
		 * @see \OCA\Mail\Migration\Version5006Date20251015082003::changeSchema
		 */
		foreach ($mailboxes->getIndexes() as $index) {
			if ($index->isUnique() && $index->spansColumns(['account_id', 'name'])) {
				$mailboxes->dropIndex($index->getName());
			}
		}

		return $schema;
	}
}
