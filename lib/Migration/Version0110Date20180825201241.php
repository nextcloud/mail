<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Db\CollectedAddressMapper;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

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
	#[\Override]
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_collected_addresses')) {
			return;
		}

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
	#[\Override]
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
			->executeStatement();
	}
}
