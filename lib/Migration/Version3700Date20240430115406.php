<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
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
	#[\Override]
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
