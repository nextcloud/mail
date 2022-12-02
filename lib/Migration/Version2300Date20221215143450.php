<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\AppFramework\Db\TTransactional;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2300Date20221215143450 extends SimpleMigrationStep {
	private IDBConnection $connection;

	use TTransactional;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$accountsTable = $schema->getTable('mail_accounts');

		// Widen refresh token
		$refreshTokenColumn = $accountsTable->getColumn('oauth_refresh_token');
		$refreshTokenColumn->setLength(3000); // Fits Microsoft and Google tokens even after encryption

		// Add dedicated access token column
		if (!$accountsTable->hasColumn('oauth_access_token')) {
			$accountsTable->addColumn('oauth_access_token', Types::TEXT, [
				'notnull' => false,
			]);
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$this->atomic(function () {
			// Migrate old data to the new column
			$qb1 = $this->connection->getQueryBuilder();
			$qb1->update('mail_accounts')
				->set('oauth_access_token', 'inbound_password')
				->where(
					$qb1->expr()->eq('auth_method', $qb1->createNamedParameter('xoauth2')),
					$qb1->expr()->isNotNull('inbound_password')
				);
			$qb1->executeStatement();

			// Delete previous data
			$qb2 = $this->connection->getQueryBuilder();
			$qb2->update('mail_accounts')
				->set('inbound_password', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
				->set('outbound_password', $qb2->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
				->where(
					$qb2->expr()->eq('auth_method', $qb2->createNamedParameter('xoauth2')),
					$qb2->expr()->isNotNull('oauth_access_token')
				);
			$qb2->executeStatement();
		}, $this->connection);
	}
}
