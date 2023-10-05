<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use JsonException;
use OCA\Mail\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version3500Date20231005091430 extends SimpleMigrationStep {
	/** @var IConfig */
	protected $config;

	/** @var IDBConnection */
	protected $connection;

	/** @var LoggerInterface */
	protected $logger;

	public function __construct(IConfig $config, IDBConnection $connection, LoggerInterface $logger) {
		$this->config = $config;
		$this->connection = $connection;
		$this->logger = $logger;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
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
