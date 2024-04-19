<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Anna Larch <anna.larch@gmx.net>
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
use OCA\Mail\Db\LocalMessage;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3700Date20240430115406 extends SimpleMigrationStep {

	public function __construct(private IDBConnection $connection) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$localMessagesTable = $schema->getTable('mail_local_messages');
		if (!$localMessagesTable->hasColumn('status')) {
			$localMessagesTable->addColumn('status', Types::INTEGER, [
				'notnull' => false,
				'default' => 0,
			]);
		}
		if (!$localMessagesTable->hasColumn('raw')) {
			$localMessagesTable->addColumn('raw', Types::TEXT, [
				'notnull' => false,
				'default' => null,
			]);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// Let's buffer this a bit
		$aMinuteAgo = time() - 60;
		$query = $this->connection->getQueryBuilder();
		$query->update('mail_local_messages')
			->set('status', $query->createNamedParameter(11, IQueryBuilder::PARAM_INT))
			->where(
				$query->expr()->lt('send_at', $query->createNamedParameter($aMinuteAgo, IQueryBuilder::PARAM_INT)),
				$query->expr()->eq('type', $query->createNamedParameter(LocalMessage::TYPE_OUTGOING, IQueryBuilder::PARAM_INT)),
			);
		$query->executeStatement();
	}
}
