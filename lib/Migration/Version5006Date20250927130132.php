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

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$mailboxes = $schema->getTable('mail_mailboxes');

		/**
		 * Make sure the old account_id+name index is gone. The DB won't allow
		 * the name length increase otherwise
		 *
		 * @see \OCA\Mail\Migration\Version3500Date20231115184458::changeSchema
		 */
		if ($mailboxes->hasIndex('UNIQ_45754FF89B6B5FBA5E237E06')) {
			$mailboxes->dropIndex('UNIQ_45754FF89B6B5FBA5E237E06');
		}

		$mailboxes->modifyColumn(
			'name',
			['length' => 1024]
		);

		return $schema;
	}
}
