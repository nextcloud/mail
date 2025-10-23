<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[ModifyColumn(table: 'mail_mailboxes', name: 'name', type: ColumnType::STRING, description: 'Increase the column length from 255 to 1024')]
class Version5006Date20250927130132 extends SimpleMigrationStep {

	/**
	 * @psalm-param Closure():ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
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
		 * Although it is not recommended to change migrations after release,
		 * we are updating this one with a safeguard to drop any existing index on account_id and name.
		 *
		 * @see \OCA\Mail\Migration\Version0161Date20190902103701::changeSchema
		 * @see \OCA\Mail\Migration\Version3500Date20231115184458::changeSchema
		 */
		foreach ($mailboxes->getIndexes() as $index) {
			if ($index->isUnique() && $index->spansColumns(['account_id', 'name'])) {
				$mailboxes->dropIndex($index->getName());
			}
		}

		$mailboxes->modifyColumn(
			'name',
			['length' => 1024]
		);

		return $schema;
	}
}
