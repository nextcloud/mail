<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
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
