<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	#[\Override]
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

	#[\Override]
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
