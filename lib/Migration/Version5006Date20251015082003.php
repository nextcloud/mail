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

class Version5006Date20251015082003 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		/**
		 * Make sure the old account_id+name index is gone for good
		 *
		 * @see \OCA\Mail\Migration\Version3500Date20231115184458::changeSchema
		 */
		$mailboxesTable = $schema->getTable('mail_mailboxes');
		if (!$mailboxesTable->hasIndex('UNIQ_45754FF89B6B5FBA5E237E06')) {
			// Nothing to do
			return null;
		}

		$mailboxesTable->dropIndex('UNIQ_45754FF89B6B5FBA5E237E06');

		return $schema;
	}

}
