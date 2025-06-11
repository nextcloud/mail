<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use Exception;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version4200Date20241210000001 extends SimpleMigrationStep {

	/**
	 * Version4200Date20241210000000 constructor.
	 *
	 * @param IDBConnection $connection
	 */
	public function __construct(
		private IDBConnection $db,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @psalm-param Closure():ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	#[\Override]
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$schema = $schemaClosure();

		$outboxTable = $schema->getTable('mail_local_messages');
		if (!$outboxTable->hasColumn('body')) {
			// Migration has already been executed
			return;
		}

		if ($outboxTable->hasColumn('body') && $outboxTable->hasColumn('body_plain') && $outboxTable->hasColumn('body_html')) {
			// copy plain type content to proper column
			$qb = $this->db->getQueryBuilder();
			$qb->update('mail_local_messages')
				->set('body_plain', 'body')
				->where($qb->expr()->eq('html', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
				->executeStatement();
			// copy html type content to proper column
			$qb = $this->db->getQueryBuilder();
			$qb->update('mail_local_messages')
				->set('body_html', 'body')
				->where($qb->expr()->eq('html', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
				->executeStatement();
		} else {
			throw new Exception('Can not perform migration step, one of the following columns is missing body, body_plain, body_html', 1);
		}
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

		$outboxTable = $schema->getTable('mail_local_messages');
		if ($outboxTable->hasColumn('body')) {
			$outboxTable->dropColumn('body');
		}
		return $schema;
	}
}
