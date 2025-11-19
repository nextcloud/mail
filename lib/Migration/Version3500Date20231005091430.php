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
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @codeCoverageIgnore
 */
class Version3500Date20231005091430 extends SimpleMigrationStep {
	/** @var IConfig */
	protected $config;

	/** @var IDBConnection */
	protected $connection;

	public function __construct(
		IConfig $config,
		IDBConnection $connection,
		protected \Psr\Log\LoggerInterface $logger
	) {
		$this->config = $config;
		$this->connection = $connection;
	}

	/**
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$provisioningTable = $schema->getTable('mail_provisionings');
		if (!$provisioningTable->hasColumn('master_password_enabled')) {
			$provisioningTable->addColumn('master_password_enabled', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}
		if (!$provisioningTable->hasColumn('master_password')) {
			$provisioningTable->addColumn('master_password', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
		}

		return $schema;
	}

}
