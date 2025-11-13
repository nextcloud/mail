<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version5006Date20251024153423 extends SimpleMigrationStep {

	public function __construct(
		private readonly IDBConnection $db,
	) {
	}
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		$accountsTable = $schema->getTable('mail_accounts');
		if (!$accountsTable->hasColumn('classification_enabled')) {
			$accountsTable->addColumn('classification_enabled', Types::BOOLEAN, [
				'default' => true,
			]);
		}
		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$qb = $this->db->getQueryBuilder();
		$qb->select('userid', 'configvalue')
			->from('preferences')
			->where($qb->expr()->eq('appid', $qb->createNamedParameter('mail')))
			->andWhere($qb->expr()->eq('configkey', $qb->createNamedParameter('tag-classified-messages')));

		$res = $qb->executeQuery();

		$falseUsers = [];
		while ($row = $res->fetch()) {
			if ($row['configvalue'] === 'false') {
				$falseUsers[] = $row['userid'];
			}
		}

		$res->closeCursor();

		$qb = $this->db->getQueryBuilder();
		$qb->update('mail_accounts')
			->set('classification_enabled', $qb->createNamedParameter(false, IQueryBuilder::PARAM_BOOL))
			->where($qb->expr()->in('user_id', $qb->createParameter('users')));
		foreach (array_chunk($falseUsers, 1000) as $chunk) {
			$qb->setParameter('users', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$qb->executeStatement();
		}

	}
}
