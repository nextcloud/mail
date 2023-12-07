<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3500Date20231115182612 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection
	) {
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
