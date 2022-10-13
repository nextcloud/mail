<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Db\CollectedAddressMapper;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0110Date20180825201241 extends SimpleMigrationStep {
	/** @var IDBConnection */
	protected $connection;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return void
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_collected_addresses')) {
			return;
		}

		/** @var IDBConnection $connection */
		$connection = $this->connection;

		// add method to overwrite tableName / entityClass
		$collectedAdressesMapper = new class($connection) extends CollectedAddressMapper {
			public function setTableName(string $tableName): void {
				$this->tableName = $tableName;
			}

			public function setEntityClass(string $entityClass): void {
				$this->entityClass = $entityClass;
			}
		};

		// change table name
		$collectedAdressesMapper->setTableName('mail_collected_addresses');
		// change entity class
		$collectedAdressesMapper->setEntityClass(CollectedAddress::class);

		$nrOfAddresses = $collectedAdressesMapper->getTotal();
		$output->startProgress($nrOfAddresses);

		$chunk = $collectedAdressesMapper->getChunk();
		while (\count($chunk) > 0) {
			$maxId = null;
			foreach ($chunk as $address) {
				/* @var $address CollectedAddress */
				$maxId = $address->getId();
				$this->insertAddress($address);
			}

			$output->advance(\count($chunk));
			$chunk = $collectedAdressesMapper->getChunk($maxId + 1);
		}
		$output->finishProgress();
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('mail_collected_addresses')) {
			$schema->dropTable('mail_collected_addresses');
		}

		return $schema;
	}

	/**
	 * 	 * Insert collected addresses to new table
	 * 	 *
	 *
	 * @param CollectedAddress $address
	 *
	 * @return void
	 */
	private function insertAddress(CollectedAddress $address): void {
		$this->connection->getQueryBuilder()
			->insert('mail_coll_addresses')
			->values([
				'id' => '?',
				'user_id' => '?',
				'email' => '?',
				'display_name' => '?'
			])
			->setParameters([
				$address->getId(),
				$address->getUserId(),
				$address->getEmail(),
				$address->getDisplayName()
			])
			->execute();
	}
}
