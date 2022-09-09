<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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

class Version2000Date20220908130842 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->getTable('mail_messages');
		if (!$messagesTable->hasColumn('imip_message')) {
			$messagesTable->addColumn('imip_message', 'boolean', [
				'notnull' => false,
				'default' => false,
			]);
		}
		if (!$messagesTable->hasColumn('imip_processed')) {
			$messagesTable->addColumn('imip_processed', 'boolean', [
				'notnull' => false,
				'default' => false,
			]);
		}
		if (!$messagesTable->hasColumn('imip_error')) {
			$messagesTable->addColumn('imip_error', 'boolean', [
				'notnull' => false,
				'default' => false,
			]);
		}
		return $schema;
	}
}
