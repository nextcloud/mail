<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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

class Version2300Date20230127093733 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$outboxTable = $schema->getTable('mail_local_messages');
		if (!$outboxTable->hasColumn('smime_sign')) {
			$outboxTable->addColumn('smime_sign', 'boolean', [
				'notnull' => false,
				'default' => false,
			]);
		}
		if (!$outboxTable->hasColumn('smime_certificate_id')) {
			$outboxTable->addColumn('smime_certificate_id', 'integer', [
				'notnull' => false,
				'length' => 4,
			]);
		}

		$accountsTable = $schema->getTable('mail_accounts');
		if (!$accountsTable->hasColumn('smime_certificate_id')) {
			$accountsTable->addColumn('smime_certificate_id', 'integer', [
				'notnull' => false,
				'length' => 4,
			]);
		}

		$aliasesTable = $schema->getTable('mail_aliases');
		if (!$aliasesTable->hasColumn('smime_certificate_id')) {
			$aliasesTable->addColumn('smime_certificate_id', 'integer', [
				'notnull' => false,
				'length' => 4,
			]);
		}

		return $schema;
	}
}
