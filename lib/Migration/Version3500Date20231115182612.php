<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3500Date20231115182612 extends SimpleMigrationStep {

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

		$mailboxesTable = $schema->getTable('mail_mailboxes');
		if (!$mailboxesTable->hasColumn('name_hash')) {
			$mailboxesTable->addColumn('name_hash', Types::STRING, ['notnull' => false]);
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// Round 1: Hash common mailbox names

		$this->connection->beginTransaction();

		foreach (['INBOX', 'Drafts', 'Sent', 'Trash', 'Junk', 'Spam', 'Archive', 'Archives'] as $name) {
			$qb = $this->connection->getQueryBuilder();
			$qb->update('mail_mailboxes')
				->set('name_hash', $qb->createNamedParameter(md5($name)))
				->where($qb->expr()->like('name', $qb->createNamedParameter($name), Types::STRING))
				->executeStatement();
		}

		$this->connection->commit();

		// Round 2: Hash everything else

		$qb = $this->connection->getQueryBuilder();
		$qb->select(['id', 'name'])
			->from('mail_mailboxes')
			->where($qb->expr()->isNull('name_hash'));
		$mailboxes = $qb->executeQuery();

		$updateQb = $this->connection->getQueryBuilder();
		$updateQb->update('mail_mailboxes')
			->set('name_hash', $updateQb->createParameter('name_hash'))
			->where($updateQb->expr()->eq('id', $updateQb->createParameter('id')));

		$this->connection->beginTransaction();

		$queryCount = 0;
		while (($row = $mailboxes->fetch()) !== false) {
			$queryCount++;

			$updateQb->setParameter('id', $row['id']);
			$updateQb->setParameter('name_hash', md5($row['name']));
			$updateQb->executeStatement();

			if ($queryCount === 50000) {
				$this->connection->commit();
				$this->connection->beginTransaction();
				$queryCount = 0;
			}
		}

		$mailboxes->closeCursor();

		$this->connection->commit();
	}
}
