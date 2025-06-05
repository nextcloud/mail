<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3800Date20240628163133 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$accounts = $schema->getTable('mail_accounts');
		$inboundPassword = $accounts->getColumn('inbound_password');

		/*
		 * The inbound_password was changed with OCA\Mail\Migration\Version0190Date20191118160843
		 * to notnull = false and default = null, but we received a report where
		 * the former migration was not applied properly.
		 */

		if ($inboundPassword->getNotnull() === true || $inboundPassword->getDefault() === '') {
			$inboundPassword->setNotnull(false);
			$inboundPassword->setDefault(null);
			return $schema;
		}

		return null;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();
		$qb->update('mail_accounts')
			->set('inbound_password', $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
			->where($qb->expr()->emptyString('inbound_password'));
		$qb->executeStatement();
	}
}
